<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Step_Counting_Change_Timestamp_Type extends CI_Migration {

    public function up()
    {
        $this->dbforge->drop_table('StepCounting');

        $this->dbforge->add_field(array(
                'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'userId' => array(
                'type' => 'CHAR',
                'constraint' => '10',
            ),
            'startDate' => array(
                'type' => 'INT',
            ),
            'stepCount' => array(
                'type' => 'INT',
                'constraint' =>  8,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('StepCounting');
    }

    public function down()
    {
        $this->dbforge->drop_table('StepCounting');
    }
}