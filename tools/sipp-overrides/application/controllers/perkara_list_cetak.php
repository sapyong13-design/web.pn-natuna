<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Perkara_list_cetak extends CI_Controller {
	function cetak(){
		$segment = $this->uri->segment_array();
		$jenis_cetak = isset($segment[3]) ? intval($segment[3]) : 0;
		if ($jenis_cetak !== 1 || empty($segment[4])) {
			show_404();
		}
		$params = $this->encrypt->decode(base64_decode($segment[4]));
		if (!preg_match('/^var_id=(-?\d+);var_tahapan=(\d+);?$/', $params, $matches)) {
			show_404();
		}
		$alur_perkara_id = intval($matches[1]);
		$keyword_segment = isset($segment[5]) ? $segment[5] : 'key';
		$keyword = ($keyword_segment === 'key' || $keyword_segment === '0')
			? '' : $this->encrypt->decode(base64_decode($keyword_segment));
		$this->load->model('perkara/perkara_m', 'perkara');
		$query = $this->perkara->getPerkaraList($alur_perkara_id, 2, 'DESC', 0, $keyword);

		$data['list_perkara'] = $query;
		$data['idalurperkara'] = $alur_perkara_id;
		$data['jenis_cetak'] = $jenis_cetak;
		
		$this->load->vars($data);
		$this->load->view('perkara_list/perkara_list_cetak');
	}
}