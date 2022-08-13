<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Redudb extends CI_Controller {

    function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->helper('url');

        $this->load->model("user_registration_model");

        $this->load->model("post_model");
        $this->load->model("dbms_model");

        $this->load->library('Datatables');
        $this->load->helper('cookie');
        $this->load->library('encryption');

        if (is_null(get_cookie('userid'))
                or
                intval(get_cookie('usertype')) > 2
                or
                intval(get_cookie('usertype')) < 1
        ) {
            if (intval(get_cookie('usertype')) == 10)
                redirect(base_url() . 'sidecar');
            else
                redirect(base_url() . 'auth');
        }
    }

    function index() {
        

        $utype = intval(get_cookie('usertype'));
        switch ($utype) {
            case 1:
            case 2:
                $this->assign('hsp');
                break;

            case 10:
                redirect(base_url() . 'sidecar');
                break;

            default:
                echo 'Fatal Error! Please Contact Site Administrator';
                break;
        }
    }
    
    
    /*
     * Provider Map
     */
    
        
    function fetch_data() {

        $v = $this->post_model->fetch_map_data();        
        echo $v;
    }
    
    function map($ptype='urg',$fcode=0) {
        
        $data['ptype'] = $ptype;
        $data['fcode'] = (!$fcode ? 0 : $fcode) ;

        $data['feecodes'] = $this->post_model->get_feecode_id($data['ptype'] ) ;  
        $data['graphdata'] = $this->post_model->fetch_graph_data($data['ptype'] ,$data['fcode'] );
        $data['prov_list'] = $this->post_model->get_categories() ;    
        $this->load->view("maps/clusterwithfiltermap", $data);
    }
    
    
    function get_state_coord() {
        
            echo $this->post_model->fetch_state_coord($_POST['st']);        

    }

    /*
     * DATA ENTRY - TRANSFERED from welcome controller (18-mar-21     
     */

    function get_zip_by_c() {
        echo $this->post_model->resolve_gps_coord($_POST['lo'] . ' ' . $_POST['la']);
    }

    function getzip() {
        echo $this->post_model->get_zip($_GET['term']);
    }
    function hasnpi($i=0,$p=0,$z=0) {
        switch ($i) {
            case 1:
                echo  $this->post_model->get_npi(1,$_POST['p'],$_POST['z']);
                break;
            case 2:
                echo $this->post_model->get_npi(2,$p,$z);
                break;
            case 3:
                echo $this->post_model->get_npi(3,$p,$z);
                break;
            
            case 4: 
            case 0:            
                $data['p'] = $p;
                $data['z'] = $z;
                $data['i'] = ($i==4?3:2);
                $this->load->view("reduviews/npi_list",$data);
                break;
        }
    }

    function provider($ptype = null, $i = 0, $v = 1) {

        if (!$ptype)
            show_404();
        
        $data['ptype'] = $ptype;
        
        $data['pid'] = ( isset($_POST['pid']) ? $_POST['pid'] : $i );
        $data['pdata'] = $this->post_model->get_provider_data($ptype, $i);
        if (!$data['pdata'] ) {            
            show_404();
        }

        $data['form_tabs'] = false;
        if ($i)
            $data['form_tabs'] = $this->post_model->get_provider_form_fields($ptype, 'tabs');
        

        $data['form_fields'] = $this->post_model->get_provider_form_fields($ptype, '', 'pinfo');


        $data['hasdataentry'] = 0;
        if (intval(get_cookie('userid')) > 0) {
            $data['hasdataentry'] = 1;
        }


        $data['isdisabled'] = '';
        if (is_null(get_cookie('userid'))) {
            $data['hasdataentry'] = 0;
            $data['isdisabled'] = 'disabled';
            if (!$i)
                redirect(base_url());
        }

        $this->load->view("reduviews/header_main", $data);

        $data['showdashinfo'] = get_cookie('dashinfo');
        $data['providertype'] = $this->post_model->get_provider_type();
        $data['pcity'] = $this->post_model->get_parent_cities();


        

//        print_r($data['pdata'] );

        $data['maxfeeid'] = 31;
        $data['ofees'] = ( $ptype === 'amb' ? $this->post_model->get_fee_data($i, $data['maxfeeid']) : null);

        $data['htypes'] = ( $ptype === 'hsp' ? $this->post_model->get_hospital_type() : null);

        $data['uctitle'] = $this->post_model->get_category_field_value($ptype, 'form_title', '1=1');
        $data['uctype'] = $this->post_model->get_category_field_value($ptype, 'cat_table_flt_val', '1=1');
        $data['urgcare_provider_types'] = $this->post_model->get_category_field_value('', 'cat_abbr', "cat_table_flt_val< 100");

        
        if ( $data['ptype'] === 'hsp' ) {
            $data['metaq'] = json_decode('{
                        "q1": "Data File Available on Website?",
                        "q2": "Cash Discounts Available in Data File?",
                        "q3": "Price Estimator on Website?",
                        "q4": "Cash Discount listed on website?",
                        "q5": "Unpublished Cash Discount Found Via Telephone?"
                    }');
        }
        
//        if ($v == 3)
            $this->load->view("reduviews/dataentry_provider_main-v3", $data);
//        else
//            $this->load->view("reduviews/dataentry_provider_main", $data);
        $this->load->view("reduviews/footer_common", $data);
        $this->load->view("reduviews/footer_main", $data);
    }

    function add_fees($ptype = null, $i = 0) {

        $ptype = isset($_POST['ptype']) ? $_POST['ptype'] : $ptype;
        $data['ptype'] = $ptype;
        $st = isset($_POST['st']) ? $_POST['st'] : '';
        $p_id = isset($_POST['p_id']) ? $_POST['p_id'] : $i;


        $data['hasdataentry'] = 0;
        if (intval(get_cookie('userid')) > 0) {
            $data['hasdataentry'] = 1;
        }


        $data['isdisabled'] = '';
        if (is_null(get_cookie('userid'))) {
            $data['hasdataentry'] = 0;
            $data['isdisabled'] = 'disabled';
            if (!$i)
                redirect(base_url());
        }


        $data['fee_type'] = "prices";
        $data['cost_data'] = $this->post_model->get_provider_fee_data("prices", $ptype, $p_id, '', 0);


//        $data['feenames'] = $data['fee_data'];
//        $data['fee_flds'] = $data['fee_data'];

        $this->load->view("reduviews/fees_dataentry-v3", $data);
    }

    function add_insurance($ptype = null, $pid = null, $st = null, $i = 0) {

        $ptype = (isset($_POST['ptype']) ? $_POST['ptype'] : $ptype);
        $data['ptype'] = $ptype;
        $st = ( isset($_POST['st']) ? $_POST['st'] : $st);
        $p_id = (isset($_POST['p_id']) ? $_POST['p_id'] : $pid);


        $data['hasdataentry'] = 0;
        if (intval(get_cookie('userid')) > 0) {
            $data['hasdataentry'] = 1;
        }


        $data['isdisabled'] = '';
        if (is_null(get_cookie('userid'))) {
            $data['hasdataentry'] = 0;
            $data['isdisabled'] = 'disabled';
            if (!$i)
                redirect(base_url());
        }

        $data['fee_type'] = "insurance";
        $data['cost_data'] = $this->post_model->get_provider_fee_data("insurance", $ptype, $p_id, $st, 0);
        $this->load->view("reduviews/fees_dataentry-v3", $data);
    }

    function hideinfo() {
        set_cookie('dashinfo', '2', 200000000, '', '/');
        echo true;
    }

    function isduplicate() {
        echo $this->post_model->check_duplicate();
    }
    
    /*
     * End data entry
     */

    /*
     * Administrative Functions
     */

    function checkzipcodes() {
        echo $this->post_model->check_input_zipcodes();
    }

    function getnearbyzip() {
        echo $this->post_model->show_near_zip($_POST['z'], $_POST['id'], $_POST['dist'], $_POST['s']);
    }

    function assign($ptype = null, $f = 0, $o = -100) {
        
        if (!$ptype)
            show_404();
        
        $data['floorno'] = $f;
        $data['page_title'] = "Assignment";
        $data['page_description'] = "list of providers";
        $data['residentlist'] = 1;
        $data['ptype'] = $ptype;


        $data['datalist'] = '{"data": "id"},
                            {"data": "provider_data"},
                            {"data": "assign_status"}';


        $data['pcity'] = ( $ptype === 'amb' ? $this->post_model->get_parent_cities() : null );


        $data['title_subheading'] = $this->post_model->get_category_field_value($ptype, 'sub_head');


        $data['dataentry'] = 1;
        $data['dataentry_staff'] = $this->user_registration_model->get_dataentry_staff($data['dataentry']);



        $data['categories'] = $this->post_model->get_categories();

        $this->load->view("reduviews/bs_interface_header", $data);
        
        
        /*
         * USE new/old version of datatable list
         */
        $data['list_version'] = 1;
        
        if( $data['list_version'] == 1 )
            $data['field_list'] = '{ title: "ID" },{ title: "Provider" },{ title: "Status" }';
        else
            $data['field_list'] = "{ data: 'id' },{ data: 'pdata' },{ data: 'st' }";
        $this->load->view("reduviews/bs_listproviders", $data);
        
        $this->load->view("reduviews/bs_interface_footer", $data);
    }

    public function assignlist($ptype = null, $fl = '99999', $assign = 0) {

        
        $uid = 0;
        if (intval(get_cookie('userid')) > 0) {
            $uid = intval(get_cookie('userid'));
        } else {
            echo 'Fatal Error! Unauthorized Access';
            exit;
        }

        $ptype = ( isset($_POST['pt']) ? $_POST['pt'] : $ptype );


        // POST data
        $postData = $this->input->post();

        // Get data
        $lver = ( isset($_POST['ver']) ? $_POST['ver'] : 1 );
        if ( $lver == 1 )
            $data = $this->post_model->get_list_data($postData, $uid, $ptype, $fl);
        else
            $data = $this->post_model->get_list_data_v2($postData, $uid, $ptype, $fl);

        echo json_encode($data);
        return;
    }

    // Show All Provider Related Filters
    function get_filters() {
        echo $this->dbms_model->build_filters($_POST['ptype']);
    }

    // Check if any data to filter
    function check_filters() {
        $flt = ( isset($_POST['flt']) ? $_POST['flt'] : false );
        $ptype = ( isset($_POST['ptype']) ? $_POST['ptype'] : false );
        if (!$flt || !$ptype)
            return false;

        $ciphertext = $this->dbms_model->check_filter_data($ptype, $flt);
        echo $ciphertext;
    }

    /*
     * Assign / Unassign Providers
     */

    
    function duplicate() {
        $pids = $_POST['pids'];
        echo $this->dbms_model->duplicate_provider($pids);
        
    }
    
    
    function paction() {
        $val = $_POST['val'];
        $pids = $_POST['pids'];
        $ptype = $_POST['ptype'];
        $attr = $_POST['attr'];

        echo $this->dbms_model->bulk_update_providers($ptype, $attr, $val, $pids);
    }

    /*
     * BULK EDIT
     */

    
    function assign_state() {
        
//        echo $_POST['ptype'] . '/' . $_POST['st']. '/' . $_POST['uid'];
        
        $st = $_POST['st'];
        $ptype = $_POST['ptype'];
        $uid = $_POST['uid'];
        $attr = 'assign_state';

        echo $this->dbms_model->bulk_update_providers($ptype, $attr, $st, $uid);
        
    }
    
    function bulkaction($i = 0) {

        echo $_POST['ptype'] . '/' . $_POST['val'] . '/' . $this->post_model->set_bulk_ids_key(!$i ? $_POST['pids'] : $i ) . '/' . $_POST['st'];
    }

    function bulk($ptype = null, $feetype = null, $pid = null, $st = null, $i = 0) {

        $data['ptype'] = $ptype;

        $this->load->view("reduviews/header_main", $data);

        $data['fee_type'] = $feetype;
        $data['cost_data'] = $this->post_model->get_provider_fee_data($feetype, $ptype, $pid, $st, 0);

        $data['is_bulk'] = 1;  // used for bulk update


        echo '<br>';
        echo '<br>';
        echo '<br>';
        echo '<br>';
        echo '<input id="p_id" type="text" value="' . $pid . '"/>';
        echo '<input id="is_bulk" type="text" value="1"/>';
//print_r($data['cost_data']);
        $this->load->view("reduviews/fees_dataentry-v3", $data);
        $this->load->view("reduviews/footer_common", $data);
        $this->load->view("reduviews/footer_main", $data);
    }

    /*
     * -------------- END Admin Functions
     */


    /*
     * Save Data
     */

//    function save_field_data() {
//        echo $this->post_model->save_field_data();
//    }
    // Save main provider data
    function save_data() {        
        echo $this->post_model->save_provider_data('providerinfo');
    }

    // save FEE / INSURACNE data
    function save_fees() {

        echo $this->post_model->save_provider_data('fees');
    }

    function save_servicearea() {
        return $this->post_model->update_service_area();
    }

    function save_contacts() {
        echo $this->post_model->save_provider_data('contactinfo');
    }

    function get_uc_timing() {
        $data['pid'] = ( isset($_POST['pid']) ? $_POST['pid'] : 0 );
        echo json_encode($this->post_model->get_time_data($data['pid']));
    }

    function get_uc_hist_timing() {
        $data['pid'] = ( isset($_POST['pid']) ? $_POST['pid'] : 0 );
        $data['bakid'] = ( isset($_POST['bakid']) ? $_POST['bakid'] : 0 );
        echo json_encode($this->post_model->get_bak_time_data($data['pid'], $data['bakid']));
    }

    function save_time() {

        echo $this->post_model->update_timings();
    }
    
    // save FEE / INSURACNE data
    function save_meta() {

        echo $this->post_model->save_provider_data('meta');
    }

    /*
     * END - Save Data
     */

    function howto() {
        $data['hid'] = 0;
        $data['hasdataentry'] = 1;
        $data['isdisabled'] = '';
        $data['pid'] = 10001;
        $data['ptype'] = 'amb';
        $this->load->view("reduviews/header_main", $data);
        $this->load->view("reduviews/create_howto", $data);
        $this->load->view("reduviews/footer_main", $data);
    }

    /*
     * Map function added 
     * 18-Dec-2021
     * 
     */

    function leaf2() {
        $data = array();
        $this->load->view("maps/leaf_ajax2", $data);
    }

    function leaf2_ajax() {
        $v = $this->post_model->fetch_map_data_ajax2();        
        echo $v;
    }

    function leaf1() {
        $data['providers'] = $this->post_model->fetch_map_data('amb');
        $this->load->view("maps/leaf_showlabel", $data);
    }

    
//    function clustermap($ptype='amb') {
//        $data['ptype'] = $ptype;
//        $data['providers'] = $this->post_model->fetch_map_data($ptype);
//        $this->load->view("maps/leafcluster", $data);
//    }
//    
    function typemap($ptype='amb') {

        $data['ptype'] = $ptype;
        $providers = $this->post_model->fetch_map_data($ptype);

        $gdata = '[';
        foreach ($providers as $record) {

            $gdata .= '{"type": "Feature",                 
                "properties": {
                "name": "' . $record->name . '"
            },
                            "geometry": {
                                "type": "Point",
                                "coordinates": [' . $record->lon . ',' . $record->lat . ']
                            }
                         },';
        }
        
        $gdata .= '{
            "type": "Feature",                 
            "properties": {
                "name": ""
            },
            "geometry": {
                "type": "Point",
                "coordinates": [-104.99404, 39.75621]
            }
            }]';
        $data['geodata'] = json_encode($gdata);


        $this->load->view("maps/leaf_typemap", $data);
    }

    

}
?>