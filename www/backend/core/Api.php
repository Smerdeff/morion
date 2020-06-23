<?php
require_once('Model.php');

abstract class Api
{

    protected $model = Model::Class;
    static $apiName = '';

    static function get_apiname()
    {
        return static::$apiName;
    }

    public $method = ''; //GET|POST|PUT|DELETE
    public $requestUri = [];
    public $requestParams = [];
    public $requestBody = '';
    protected $action = ''; //name function for action

    public function __construct()
    {

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: *");
        header("Content-Type: application/json");

        //Array GET request split '/'
        $this->requestUri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
        array_shift($this->requestUri); #remove api
        array_shift($this->requestUri); #remove model

        $this->requestParams = $_REQUEST;
        $this->requestBody = file_get_contents('php://input');

        //Check Header
        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception("Unexpected Header");
            }
        }

    }

    public function response($data, $status = 500)
    {
        header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function requestStatus($code)
    {
        $status = array(
            200 => 'OK',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );
        return ($status[$code]) ? $status[$code] : $status[500];
    }


    /**
     * Method GET
     * http://domen/models
     * @return string
     */
    public function readAction()
    {
        $data = $this->model::read();
        if ($data) {
            return $this->response($data, 200);
        }
        return $this->response('Data not found', 404);
    }


    /**
     * Method DELETE
     * http://domen/models/id
     * @return string
     */
    public function destroyAction()
    {
        $id = array_shift($this->requestUri);
        if (!$id || !$this->model::retrieve($id)) {
            return $this->response("Data with id=$id not found", 404);
        }
        if ($this->model::destroy($id)) {
            return $this->response('Data deleted.', 200);
        }
        return $this->response("Delete error", 500);
    }

    /**
     * Method PUT
     * http://domen/models/id + body json
     * @return string
     */
    public function updateAction()
    {
        $id = array_shift($this->requestUri);
        if (!$id || !$this->model::retrieve($id)) {
            return $this->response("Data with id=$id not found", 404);
        }

        if ($this->requestBody) {
            $data = json_decode($this->requestBody, TRUE);

            if ($data) {
                if ($this->model::update($id, $this->model::validate($data))) {
                    return $this->response('Data updated.', 200);
                }
            }
        }
        return $this->response("Update error1", 400);
    }

    /**
     * Method POST
     * http://domen/models + body json
     * @return string
     */
    public function createAction()
    {
        if ($this->requestBody) {
            $data = json_decode($this->requestBody, TRUE);
            if ($data) {
                if ($this->model::create($this->model::validate($data))) {
                    return $this->response('Data updated.', 200);
                }
            }
        }

        return $this->response("Create error", 500);
    }

}