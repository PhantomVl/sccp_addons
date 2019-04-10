<?php

/* Declare a namespace, available from PHP 5.3 forward. */

namespace cisco;

class service {

//    public $class_error =  array('eroor' => 'test');
    public $class_error = array();
    public $dev_login = false;
    public $conf_db = array();
    public $conf_ami = array();
    public $xml_url = '';
    public $host_url = '';
    public $conf_path;
    public $page_title;
    public $page_text;
    public $view_type = 'cxml';
    public $user_agent;
//    public $sys_mode = 'ami';
    public $sys_mode = 'hw';
    
    public $req_data = array();
    private $form_path = '';

    public function __construct() {
// Init
        $this->conf_path = __DIR__;
        if (file_exists(__DIR__ . '/cisco_service.ini')) {
            $int_conf = parse_ini_file(__DIR__ . "/cisco_service.ini", true);
            $this->init_path($int_conf);
        } else {
            die('No Config File');
        }
        $this->view_action = '';    
        $host_param = parse_url($_SERVER["REQUEST_URI"]);
        $this->host_url = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $host_param['path'];
        //$this->host_query = explode("&", $host_param['query']);
        $request = $_REQUEST;
        if (empty($_REQUEST)) {
            $this->view_action = 'error';
            $request['id'] = rand();
        } 
        
//        $this->xml_url = $this->host_url . '?' . $this->array_key2str($request, '=', '&amp;');
        $this->xml_url = $this->host_url . '?' . $this->array_key2str($request, '=', '&amp;', array('exclude' => array('action')) );
        $driverNamespace = "\\cisco\service";
        if (class_exists($driverNamespace, false)) {
            foreach (glob(__DIR__ . "/service.inc/*.class.php") as $driver) {
                if (preg_match("/\/([a-z1-9]*)\.class\.php$/i", $driver, $matches)) {
                    $name = $matches[1];
                    $class = $driverNamespace . "\\" . $name;
                    if (!class_exists($class, false)) {
                        include($driver);
                    }
                    if (class_exists($class, false)) {
                        $this->$name = new $class($this);
                    } else {
                        throw new \Exception("Invalid Class inside in the include folder" . print_r($class));
                    }
                }
            }
        } else {
            return;
        }
        $this->form_path = __DIR__ . '/views/default/';
        if (!empty($request['locale'])) {
            $loc_code = $this->extconfigs->getextConfig('locale2code', $request['locale']);
            if (!empty($loc_code)) {
                if (file_exists(__DIR__ . '/views/' . $loc_code . '/service.xml.php')) {
                    $this->form_path = __DIR__ . '/views/' . $loc_code . '/';
                }
            }
        }
        if (!empty($this->conf_ami)) {
            $this->ami->config['asmanager']= $this->conf_ami;
//            if ($this->ami->connect($this->conf_ini['ami']['HOST'] . ':' . $this->conf_ini['ami']['PORT'],
//                                    $this->conf_ini['ami']['USER'], $this->conf_ini['ami']['PASS'], 'off') === false) {
            if ($this->ami->connect(null, null, null, 'off')=== false) {
                  throw new \RuntimeException('Could not connect to Asterisk Management Interface.');
            }
        }

        
        if ($this->conf_db['db_engine'] == 'mysql') {
            $this->dbinterface->_init($this->conf_db);
        }

        $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
        if ($this->user_agent == "html") {
            $this->view_type = "html";
        } else {
            $this->view_type = "cxml";
        }

//        $this->aminterface->_init($this->conf_ami);
    }

    private function init_path($int_conf) {
        $amp_dbkey = array('AMPDBUSER' => 'username', 'AMPDBPASS' => 'password', 'AMPDBHOST' => 'host', 'AMPDBNAME' => 'db','AMPDBENGINE' => 'db_engine');
        $amp_amikey = array('ASTMANAGERHOST' =>'server' , 'AMPMGRPASS' => 'secret', 'ASTMANAGERPORT' => 'port' , 'AMPMGRUSER' => 'username' );
        $amp_load = false;
        if (!empty($int_conf['general'])) {
            $_conf = $int_conf['general'];
            if (!empty($_conf['page_title'])) {
                $this->page_title = $_conf['page_title'];
            }
            if (!empty($_conf['mode'])) {
                $this->sys_mode  = $_conf['mode'];
            }
            // Try load config from asterisk 
            if (!empty($_conf['amportal'])) {
                if (file_exists($_conf['amportal'])) {
                    $amp_load = true;
                    $amp =  parse_ini_file($_conf['amportal'], true);
                    $this->conf_db = $this->copy_array($amp , $amp_dbkey);
                    $this->conf_ami = $this->copy_array($amp , $amp_amikey);
                }
            }
            if ( !$amp_load ) {
                if (!empty($int_conf['DB'])) {
                    $_conf = $int_conf['DB'];
                    $this->conf_db = $this->copy_array($_conf, $amp_dbkey, true);
                }
                if (!empty($int_conf['AMI'])) {
                    $_conf = $int_conf['AMI'];
                    $this->conf_ami = $this->copy_array($_conf, $amp_amikey, true);
                }
            }
        }
    }

    public function request_processing() {
        $request = $_REQUEST;
//        $req_fld = Array('sessionid',  'locale', 'name',);
        $req_fld = Array('name',  'sessionid', 'userid', 'pincode', 'locale');
        $msg = '';
        $this->view_action = '';
        $this->dev_login = false;

        if (empty($request)) {
            $this->view_action = 'error';
            return array();
        } 
        
        if (!empty($request['name'])) {
            if (!empty($this->Device_login($request))) {
                $this->dev_login = true;
            }
        }
        
        if (empty($request['action'])) {
            return array();
        }

        $cmd_id = $request['action'];
        $this->view_action = $cmd_id;

        $send_rep = $this->copy_array($request, $req_fld, true);
        $empy_rep = $this->get_empty_key($request, $req_fld);
        
        if (isset($request['sessionid'])) {
            $this->sessionid = $request['sessionid'];
        } else {
            if (!in_array('name', $empy_rep)) {
                if ($send_rep['name'] != '#DEVICENAME#') {
                    $send_rep['sessionid'] = $this->Device_session($send_rep);
                }
            }
        }

        switch ($cmd_id) {
            case 'loginform':
                if (in_array('name', $empy_rep)) {
                    $send_rep['name'] = '#DEVICENAME#';
                } else { // Да откуда у нас в форме логина  sessionid ????? Если имя прислали то тянем сесию
                    if (in_array('sessionid', $empy_rep)) {
                        $send_rep['sessionid'] = $this->Device_session($send_rep);
                    }
                    // тут Можно и отругаться ! 
                }
                if (isset($this->sessionid)) {
                    $send_rep['sessionid'] = $this->sessionid;
                }
                $this->xml_url = $this->host_url . '?' . $this->array_key2str($send_rep, '=', '&amp;') ;
                break;
            case 'login':
                if (in_array('sessionid', $empy_rep)) {
                    $request['sessionid']  = $this->Device_session($send_rep);
                }
                if (empty($request['sessionid'] )) {
                    $this->page_text = 'Session ID ?';
                    $this->view_action = 'loginform';
                    $this->xml_url = $this->host_url . '?' . $this->array_key2str($send_rep, '=', '&amp;') ;
                    return $resp;
                }
                $empy_rep = $this->get_empty_key($request, Array('name',  'sessionid', 'userid', 'pincode', 'locale'));
                if (!empty($empy_rep)) {
                    $this->page_text = 'Request Faild :'. print_r($empy_rep,1);
                    $this->view_action = 'error';
                    break;
                }
                $resp = $this->validate_login($send_rep);

                if ($resp['result'] == false) {
                    $this->view_action = 'error';
                    $this->page_text = $resp['error_msg']  . ":" . $resp['ami'];;
                    return $resp;
                }
                $this->view_action = 'info';
                $this->page_text =  'Login Successfull (Timeout:' . $resp['ami']['TimeOut'] . ')'; 
                if ($this->sys_mode =='ami') { // If on Driver command 
                    break;
                }
                // DB Version 
                
                if ($resp['killother'] == 'off') {
                    if ($request['logoff'] == 'yes') {
                        $resp['killother'] = 'on';
                    } else {
                        $this->page_text = 'Logoff othe device ?';
                        $this->view_action = 'login2';
                        return $resp;
                    }
                }

                $this->User_login($send_rep); // Check ??
                
                $this->page_text = 'Login Successfull';
                if ($resp['killother'] == 'on') {
                    $tmp_res = $this->Device_logout($send_rep);
                }
                
                break;
            case 'logout':
                if (in_array('sessionid', $empy_rep)) {
                    $request['sessionid']  = $this->Device_session($send_rep);
                }
                
                $empy_rep = $this->get_empty_key($request, Array('name',  'sessionid'));                
                if (!empty($empy_rep)) {
                    $this->page_text = 'Request Faild :'. print_r($empy_rep,1);
                    $this->view_action = 'error';
                    break;
                }
                
                $resp = $this->User_logout($send_rep);
                $this->view_action = 'info';
                $this->page_text = 'logged out';
                return $resp;
                break;
                
                
            default:
                $this->xml_url = $this->host_url . '?' . $this->array_key2str($send_rep, '=', '&amp;') ;
                break;
        }
    }

    public function ServiceShowPage() {
        $request = $_REQUEST;
        $action =''; 
//        $action = !empty($request['action']) ? $request['action'] : '';
        $action = !empty($this->view_action) ? $this->view_action : $action;

        switch ($action) {
            case 'loginform':
                setcookie ('sessionid', $this->sessionid, $expires = time()+1800, $path = "/"); // Where are you get session ????? 
                header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 5)));		/* 5 min timeout */
                $this->pagedata = array(
                    "general" => array(
                        "name" => _("Login"),
                        "page" => implode(".", array($this->form_path . $action, $this->view_type, 'php'))
                    ),
                );
                break;
            case 'login2':
                setcookie ('sessionid', $this->sessionid, $expires = time()+1800, $path = "/"); // Where are you get session ????? 
                header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 5)));		/* 5 min timeout */
                $this->pagedata = array(
                    "general" => array(
                        "name" => _("Login"),
                        "page" => implode(".", array($this->form_path . $action, $this->view_type, 'php'))
                    ),
                );
                break;

            case 'logout':
                $this->pagedata = array(
                    "general" => array(
                        "name" => _("Logout"),
                        "page" => implode(".", array($this->form_path . 'info', $this->view_type, 'php'))
                    ),
                );
                break;

            case 'error':
                $this->pagedata = array(
                    "general" => array(
                        "name" => _("Logout"),
                        "page" => implode(".", array($this->form_path . 'info', $this->view_type, 'php'))
                    ),
                );
                break;
            
             case 'info':
                $this->pagedata = array(
                    "general" => array(
                        "name" => _("Info"),
                        "page" => implode(".", array($this->form_path . 'info', $this->view_type, 'php'))
                    ),
                );
                break;

            default:
                $this->pagedata = array(
                    "general" => array(
                        "name" => _("General"),
                        "t" => $this->dbinterface->info(),
                        "page" => implode(".", array($this->form_path . 'service', $this->view_type, 'php'))
                    ),
                );
                break;
        }

        if (!empty($this->pagedata)) {
            foreach ($this->pagedata as &$page) {
                ob_start();
                if (file_exists($page['page'])) {
                    include($page['page']);
                    $page['content'] = ob_get_contents();
                } else {
                    $page['content'] = 'file not found : ' . $page['page'];
                }
                ob_end_clean();
            }
        }
        return $this->pagedata;
    }

    /*
     *   DB / AMI Function Use to abstract interface 
     */

    private function User_login($param = array()) {
        if ($this->sys_mode =='ami') {
            return  true;
        } else {
            $this->dbinterface->get_device_info('user_login', $param);
            $this->ami_commands('restart_device', $param);
            
            return  true;
        }
    }

    private function User_logout($param = array()) {
        if ($this->sys_mode =='ami') {
            return  true;
        } else {
            $this->ami_commands('restart_device', $param);            
            return $this->dbinterface->get_device_info('user_logout', $param);
        }
    }

    private function Validate_login($param = array()) {
        if ($this->sys_mode =='ami') {
            return $this->ami_commands('RequestLogin', $param);
        } else  {
            return $this->dbinterface->get_device_info('validate_login', $param);
        }
    }

    private function Device_login($param = array()) {
        if ($this->sys_mode =='ami') {
            return  false;
        } else {
            return $this->dbinterface->get_device_info('device_login', $param);
        }
    }

    private function Device_logout($param = array()) {
        if ($this->sys_mode =='ami') {
            return  true;
        } else {
            $resp = $this->dbinterface->get_device_info('device_logout', $param);
            foreach ($resp as $value) {
                $this->ami_commands('restart_device', array('name' => $value['name']));
            }
            return $resp;
        }
        
    }
    private function Device_session($param = array()) {
        if ($this->sys_mode =='ami') {
            $resp = $this->ami_commands('RequestSession',$param);
            if ($resp['result'] === true) {
                $this->sessionid = $resp['ami']['SessionID'];
                return $this->sessionid;
            }
        } else {
            return $this->dbinterface->get_device_info('device_rand', $param);
        }
        return null;
    }

    private function log($message, $level = self::LOG_INFO)
    {
        if ($level <= $this->logLevel) {
            error_log(date('r').' - '.$message);
        }
    }
    
//   
//      Send to AMI interface Comsnds 
//      
    
    public function ami_commands($cmd = '', $param = array()) {
        $actionid = rand();
        switch ($cmd) {
            case 'reset_device':
                $ami_result = $this->ami->sendRequest('SCCPDeviceRestart', array('ActionID' => $actionid, 'Devicename' => $param['name'], 'Type' => 'reset'));
                break;
            case 'restart_device':
                $ami_result = $this->ami->sendRequest('SCCPDeviceRestart', array('ActionID' => $actionid, 'Devicename' => $param['name'], 'Type' => 'restart'));
                break;
            case 'RequestSession':
                $ami_result = $this->ami->sendRequest('SCCPUserRequestSession', array('ActionID' => $actionid, 'DeviceID' => $param['name']));
//                return array('result' => false,'action'=> $cmd, 'ami' => $ami_result, 'param'=>$param);
                break;
            case 'RequestLogin':
                $ami_result = $this->ami->sendRequest('SCCPUserProgressLogin', array('ActionID' => $actionid, 'DeviceID' => $param['name'], 'SessionID' => $param['sessionid'],
                                                                                     'UserID' => $param['userid'],'Pincode' => $param['pincode']));
                break;
            default:
                return array('result' => false, 'error'=> 'noId');
                break;
        }
        if (isset($ami_result['Response']) && $ami_result['Response'] === 'Success' &&
            isset($ami_result['ActionID']) && $ami_result['ActionID'] == $actionid)
        {
            return array('result' => true, 'ami' => $ami_result);
        }
        return array('result' => false, 'ami' => $ami_result);
    }
    
    
//   
//      Check Requered key $source 
//      
    private function get_empty_key($source = Array(),$map = Array()) {
        $res = Array();
        foreach ($map as $key) {
            if (!array_key_exists($key, $source)) {
                $res[] .= $key;
            } else {
                if (empty($source[$key])) {
                    $res[] .= $key;
                }
            }
        }
        return $res;
    }

//      Copy From $source -> result 
//      MAP: result[MAP_Key] = $source[MAP_Value]
//      $ignore - Skip not found Key

    private function copy_array($source = Array(),$map = Array(), $value_key = false ) {
        $res = Array();
        foreach ($map as $key => $value) {
            $source_key = ($value_key) ? $value : $key; 
            if (array_key_exists($source_key, $source)) {
                $res[$value] = $source[$source_key];
            } 
        }
        return $res;
    }

    public function array_key2str($data = Array(), $keydelimer = '=', $rowdelimer = ';', $filter =array() ) {
        $res = '';
        $skip = false;
        foreach ($data as $key => $value) {
            $skip = false;
            if (!empty($res)) {
                $res .= $rowdelimer;
            }
            if (isset($filter)) {
                if (is_array($filter['exclude'])) {
                    if (in_array($key,$filter['exclude'])) {
                        $skip = true; 
                    }
                }
                if (is_array($filter['include'])) {
                    if (!is_array($key,$filter['include'])) {
                        $skip = true; 
                    }
                }
            }
            if (!$skip) {
                $res .= $key . $keydelimer . $value;
            }
        }
        return $res;
    }

}
