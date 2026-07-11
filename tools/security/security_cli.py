#!/usr/bin/env python3
"""Private Joomla security CLI. Standard library only; never serves HTTP."""
import argparse, hashlib, json, re, sys
from pathlib import Path

EXIT_FINDINGS=2
TRACK={".php", ".htaccess", ".ini"}
SUSPICIOUS={"eval(base64_decode":"encoded eval", "assert($_":"request assertion", "shell_exec(":"shell execution", "passthru(":"process execution", "system($_":"request command", "preg_replace('/e":"legacy evaluated regex"}
DEFAULT_WORDS=("judi","judol","slot","gacor","casino","togel","jackpot","maxwin","betting","poker","roulette")

def emit(kind, findings, as_json, summary=None):
    out={"scanner":kind,"finding_count":len(findings),"findings":findings}
    if summary is not None: out["summary"]=summary
    if as_json: print(json.dumps(out, ensure_ascii=False, sort_keys=True))
    else:
        print(f"{kind}: {len(findings)} finding(s)")
        for f in findings: print("- "+"; ".join(f"{k}={v}" for k,v in f.items()))
        if summary: print("summary: "+json.dumps(summary, ensure_ascii=False, sort_keys=True))
    return EXIT_FINDINGS if findings else 0

def selected(root):
    for p in root.rglob("*"):
        if not p.is_file() or p.is_symlink(): continue
        n=p.name.lower()
        if p.suffix.lower() in TRACK or n in (".htaccess","configuration.php"): yield p

def digest(p):
    h=hashlib.sha256()
    with p.open("rb") as f:
        for b in iter(lambda:f.read(131072),b""): h.update(b)
    return h.hexdigest()

def integrity(args):
    root=Path(args.root).resolve(); bp=Path(args.baseline).resolve()
    if args.action=="baseline":
        files={p.relative_to(root).as_posix():digest(p) for p in selected(root) if p.resolve()!=bp}
        bp.parent.mkdir(parents=True,exist_ok=True); bp.write_text(json.dumps({"version":1,"root":str(root),"files":files},sort_keys=True,indent=2)+"\n",encoding="utf-8")
        print(json.dumps({"baseline":str(bp),"file_count":len(files)}) if args.json else f"Baseline written: {len(files)} files")
        return 0
    base=json.loads(bp.read_text(encoding="utf-8"))["files"]
    current={p.relative_to(root).as_posix():p for p in selected(root) if p.resolve()!=bp}; findings=[]
    for name,p in sorted(current.items()):
        status="new" if name not in base else ("modified" if digest(p)!=base[name] else None)
        if status: findings.append({"type":status,"path":name})
        if p.suffix.lower()==".php":
            text=p.read_text(encoding="utf-8",errors="ignore").lower()
            for token,label in SUSPICIOUS.items():
                if token in text: findings.append({"type":"suspicious_php","path":name,"indicator":label})
    for name in sorted(set(base)-set(current)): findings.append({"type":"missing","path":name})
    return emit("integrity",findings,args.json)

def accounts(args):
    data=json.loads(Path(args.input).read_text(encoding="utf-8")); admins=data.get("admins",[])
    findings=[]; safe=[]
    for a in admins:
        item={"name":str(a.get("name","")),"username":str(a.get("username","")),"groups":[str(x) for x in a.get("groups",[])],"mfa":bool(a.get("mfa",False))}; safe.append(item)
        if not item["mfa"]: findings.append({"type":"admin_without_mfa","name":item["name"],"username":item["username"]})
    summary={"admins":safe,"admin_count":len(admins),"session_count":len(data.get("sessions",[])),"token_count":len(data.get("tokens",[]))}
    return emit("accounts",findings,args.json,summary)

def judol(args):
    words=set(DEFAULT_WORDS)
    if args.keywords: words.update(x.strip().lower() for x in Path(args.keywords).read_text(encoding="utf-8").splitlines() if x.strip() and not x.lstrip().startswith("#"))
    allow=set()
    if args.allowlist: allow.update(x.strip().lower() for x in Path(args.allowlist).read_text(encoding="utf-8").splitlines() if x.strip() and not x.lstrip().startswith("#"))
    findings=[]
    def scan(source,ident,text):
        low=text.lower()
        for w in sorted(words):
            if w not in allow and re.search(r"(?<!\w)"+re.escape(w)+r"(?!\w)",low): findings.append({"type":"judol_indicator","source":source,"id":str(ident),"keyword":w})
    if args.records:
        for row in json.loads(Path(args.records).read_text(encoding="utf-8")):
            scan(str(row.get("source","db")),row.get("id","unknown")," ".join(str(row.get(k,"")) for k in ("title","alias","body","meta","link","params")))
    root=Path(args.root)
    for p in root.rglob("*"):
        if p.is_file() and not p.is_symlink() and (p.suffix.lower() in {".xml",".html",".htm",".txt",".php"} or "sitemap" in p.name.lower()):
            scan("filesystem",p.relative_to(root).as_posix(),p.read_text(encoding="utf-8",errors="ignore"))
    return emit("judol",findings,args.json)

def preflight(args):
    d=json.loads(Path(args.input).read_text(encoding="utf-8")); findings=[]
    for key in ("https","admin_outer_gate","mfa_admins","origin_restricted","config_outside_webroot","archive_outside_webroot"):
        if d.get(key) is not True: findings.append({"type":"failed_check","check":key})
    required={"content-security-policy","strict-transport-security","x-content-type-options","referrer-policy"}; got={str(x).lower() for x in d.get("security_headers",[])}
    for h in sorted(required-got): findings.append({"type":"missing_header","check":h})
    perms=d.get("permissions",{})
    if str(perms.get("files")) not in {"0644","0444"}: findings.append({"type":"unsafe_permission","check":"files"})
    if str(perms.get("dirs")) not in {"0755","0555"}: findings.append({"type":"unsafe_permission","check":"dirs"})
    return emit("preflight",findings,args.json)

def parser():
    p=argparse.ArgumentParser(); sub=p.add_subparsers(dest="cmd",required=True)
    i=sub.add_parser("integrity"); i.add_argument("action",choices=("baseline","check")); i.add_argument("root"); i.add_argument("--baseline",required=True); i.add_argument("--json",action="store_true"); i.set_defaults(func=integrity)
    a=sub.add_parser("accounts"); a.add_argument("input"); a.add_argument("--json",action="store_true"); a.set_defaults(func=accounts)
    j=sub.add_parser("judol"); j.add_argument("--root",required=True); j.add_argument("--records"); j.add_argument("--keywords"); j.add_argument("--allowlist"); j.add_argument("--json",action="store_true"); j.set_defaults(func=judol)
    f=sub.add_parser("preflight"); f.add_argument("input"); f.add_argument("--json",action="store_true"); f.set_defaults(func=preflight)
    return p

def main():
    try: return parser().parse_args().func(parser().parse_args())
    except (OSError,ValueError,KeyError,json.JSONDecodeError) as e:
        print(f"error: {type(e).__name__}",file=sys.stderr); return 3
if __name__=="__main__": raise SystemExit(main())
