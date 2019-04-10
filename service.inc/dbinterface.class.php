<?php

/**
 * 
 * Core Comsnd Interface 
 * 
 * 
 */

namespace cisco\service;

class dbinterface {

    private $dbconfig = array();
    private $parent_class = null;
    protected $db_mysql = null;
    public $autoReconnect = true;
    protected $autoReconnectCount = 0;
    protected $_lastQuery;

    public function __construct($parent_class = null, $params = array()) {
        $this->parent_class = $parent_class;
        $this->db_mysql = null;
        if (is_array($params) && (!empty($params))) {
            $this->_init($params);
        } else {
//            $this->_init($this->parent_class->conf_db);
        }
    }

    public function _init($params = array()) {
        $def_param = array('host' => 'localhost', 'username' => null, 'password' => null, 'db' => null, 'port' => 3306, 'socket' => null, 'charset' => 'utf8');
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                if (array_key_exists(strtolower($key), $def_param)) {
                    $def_param[strtolower($key)] = $value;
                }
            }
        }
        $this->dbconfig = $def_param;
        $this->db_mysql = $this->connect($this->dbconfig);
    }

    public function info() {
        $Ver = '1.0.1';
        $ver_sql = '1';
        if (isset($this->db_mysql)) {
            $ver_sql = mysqli_get_server_version($this->db_mysql);
        }

        return Array('Version' => $Ver,
            'about' => 'Data access interface ver: ' . $Ver,
            'sql_server' => $ver_sql
        );
    }

    public function connect($config = array()) {
        $params = array_merge($this->dbconfig, $config);
        if (!isset($params) || empty($params)) {
            throw new Exception('Connection profile not set');
        }

        if (empty($params['host']) && empty($params['socket'])) {
//            return Array('error'=> 'MySQL host or socket is not set',$this -> dbconfig);
            throw new Exception('MySQL host or socket is not set');
        }
        if (isset($this->db_mysql)) {
            $this->disconnect();
        }
        $mysqlic = new \ReflectionClass('mysqli');

        $connet_param = array_values($params);
        $charset = array_pop($connet_param);

        $mysqli = $mysqlic->newInstanceArgs($connet_param);
        if ($mysqli->connect_error) {
//            return Array('error'=> 'Connect Error ' . $mysqli->connect_errno . ': ' . $mysqli->connect_error, $mysqli->connect_errno);
            throw new Exception('Connect Error ' . $mysqli->connect_errno . ': ' . $mysqli->connect_error, $mysqli->connect_errno);
        }
        if (!empty($charset)) {
            $mysqli->set_charset($charset);
        }
        $this->db_mysql = $mysqli;
        return $mysqli;
    }

    public function disconnect() {
        if (!isset($this->db_mysql))
            return;
        $this->db_mysql->close();
        unset($this->db_mysql);
    }

    private function resultSetToArray($result_set) {
        $array = array();
        while (($row = $result_set->fetch_assoc()) != false) {
            $array[] = $row;
        }
        return $array;
    }

    public function dbQuery($query) {
        $mysqli = $this->db_mysql;
        $res = $mysqli->query($query, MYSQLI_USE_RESULT);
        return $res;
    }

    public function dbGet($query) {
        $mysqli = $this->db_mysql;
        $res = $mysqli->query($query, MYSQLI_USE_RESULT);
        if (!$res)
            return false;
        return $this->resultSetToArray($res);
    }

    function get_device_info($function = '', $param = array()) {
        switch ($function) {
            case 'device_login':
                $res = $this->device_login($param);
                break;
            case 'device_logout':
                $res = $this->Device_logout($param);
                break;
            case 'validate_login':
                $res = $this->validate_login($param);
                break;
            case 'user_login':
                $param['profile_id'] = $this->get_profile_id($param);
                $res = $this->User_login($param);
                break;
            case 'user_logout':
                $res = $this->User_logout($param);
                break;
            case 'device_rand':
                $res = $this->Device_chek($param);
                break;
            default:
                $res = array();
                break;
        }
        return $res;
    }

    private function Device_chek($param = array()) {
        $sql = "SELECT CRC32(concat(`name`,`type`)) from sccpdevice where name='" . $param['name'] . "';";
        $req = $this->dbGet($sql);
        if (empty($req)) {
            return null;
        }
        return $req[0];
    }

    private function device_login($param = array()) {
        $sql = "SELECT name, _description from  sccpdevice where _profileid!='0' and name='" . $param['name'] . "';";
        $req = $this->dbGet($sql);
        if (!empty($req)) {
            return array('result' => false, 'error_msg' => 'User alredy  login the device :' . $req[0]['name']);
        }
    }

    private function User_login($param = array()) {
        $id = (empty($param['profile_id'])) ? 0 : $param['profile_id'];
        $sql = "UPDATE `sccpdevice` SET `_profileid`='" . $id . "', `_loginname`='" . $param['userid'] . "' WHERE  `name`='" . $param['name'] . "';";
        $req = $this->dbQuery($sql);
        return $req;
    }

    /*
     *   Logout User 
     */

    private function User_logout($param = array()) {
        $sql = "UPDATE `sccpdevice` SET `_profileid`='0' WHERE  `name`='" . $param['name'] . "';";
        $req = $this->dbQuery($sql);
        return $req;
    }

    private function Device_logout($param = array()) {
        $sql = "SELECT name from sccpdevice where `_loginname`='" . $param['userid'] . "' and _profileid != '0' and `name` != '" . $param['name'] . "';";
        $req = $this->dbGet($sql);
        if (!empty($req)) {
            foreach ($req as $value) {
                $this->User_logout(array('name' => $value['name']));
            }
        }
        return $req;
    }

    /*
     * Get User Profile
     * 
     */

    private function get_profile_id($param = array()) {
        $sql = "SELECT name, roaminglogin, auto_logout, homedevice from  sccpuser where `name`='" . $param['userid'] . "';";
        $req = $this->dbGet($sql);     // User validate 
        if (empty($req)) {
            return 0;
        }
        $res = (empty($req[0]['homedevice'])) ? 1 : 2;
        return $res;
    }

    /*
     * Check User and Permission
     * 
     */

    private function validate_login($param = array()) {
//        $sql = "SELECT name, _rouminglogin from  sccpline where `name`='" . $param['user'] . "' and `pin`='" . $param['pin'] . "';";

        $sql = "SELECT name, roaminglogin, auto_logout, homedevice from  sccpuser where `name`='" . $param['userid'] . "' and `pin`='" . $param['pincode'] . "';";
        $req = $this->dbGet($sql);
        $res = array('result' => false, 'error_msg' => 'Internal Error');
        if (empty($req)) {
            return array('result' => false, 'error_msg' => 'Login false');
        }
        $romuming = $req[0]['roaminglogin'];
        if ($romuming == 'off') {
            return array('result' => false, 'error_msg' => "User can't rouming login permission");
        }
        $dev_map = (empty($req[0]['homedevice'])) ? $param['userid'] : $req[0]['homedevice'];
        $auto_kill = $req[0]['auto_logout'];

        $sql = "SELECT * from  sccpbuttonconfig where `ref`='" . $dev_map . "';";
        $req = $this->dbGet($sql);     // User validate 
        if (empty($req)) {
            return array('result' => false, 'error_msg' => 'No found user profile :' . $dev_map);
        }

        if ($romuming == 'on') {
            $sql = "SELECT name, _description from  sccpdevice where `_loginname`='" . $param['userid'] . "' and _profileid != '0' and name !='" . $param['name'] . "';";
            $req = $this->dbGet($sql);
            if (!empty($req)) {
                $res = array('result' => true, 'killother' => $auto_kill, 'error_msg' => 'Logout othe device ?' . $req[0]['name']);
                if ($auto_kill == 'block') {
                    $res = array('result' => false, 'killother' => $auto_kill, 'error_msg' => 'Logout on othe device before login');
                }
            } else {
                $res = array('result' => true, 'error_msg' => 'User validate Ok');
            }
        } else {
            $res = array('result' => true, 'error_msg' => 'User validate Ok');
        }

//        $res = array('result' => true, 'error_msg' => 'User validate Ok');        
        // Zone Condition 
        // Time Condition 
        return $res;
    }

}
