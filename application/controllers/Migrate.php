<?php

class Migrate extends CI_Controller
{

    public function __construct() 
    {
        parent::__construct();
        $this->load->model('migrate_model');
    }

    public function index()
    {
        $this->load->library('migration');

        if ($this->migration->current() === FALSE)
        {
            show_error($this->migration->error_string());
        }
        else 
        {
            echo "数据库迁移成功";
        }
    }

    public function resetStepCountingData() 
    {
    	echo $this->migrate_model->resetStepCountingData();
    }

}