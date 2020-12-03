<?php
class DbUtil extends Base
{
    public const DATASET_VAR_NAME = '__dataset__';

    protected $_db;
    protected $_tbl;
    protected $_modifyOptions = array();
    protected $_editFormFields = array();
    protected $_editPanelKeys = '{ }';

    public function __construct($tbl)
    {
        $app = Sys::app();
        $db = $app->db;
        if (!$db) $app->error('Cannot connect to database!');
        $this->_db = $db;
        $this->_tbl = $db->tbl($tbl);
    }

    public function tbl($tableName = '')
    {
        if (!$tableName) return $this->_tbl;
        return $this->_db->tbl($tableName);
    }

    private function _prepareData($keys, $data)
    {
        $rec = array();
        foreach ($keys as $key => $attr) {
            if (strstr($attr, 'required') && empty($data[$key])) {
                return false;
            }
            if (strstr($attr, 'bool')) {
                $rec[$key] = empty($data[$key]) ? 0 : 1;
            } else if (isset($data[$key])) {
                $rec[$key] = $data[$key];
            }
        }
        return $rec;
    }

    public function modify($data, $keys = null)
    {
        $app = Sys::app();
        if (!$app->canEdit) $app->error('Privileges required!');
        if (!empty($data['DEL'])) {
            if (!empty($data['id'])) {
                $id = $data['id'];
                if ($this->_tbl->deleteById($id) !== false) return $id;
                $app->error('Failed to delete!');
            }
        } elseif (isset($keys)) {
            $rec = $this->_prepareData($keys, $data);
            if ($rec !== false) {
                if (!empty($data['id'])) {
                    $id = $data['id'];
                    if ($this->_tbl->updateById($id, $rec) !== false) return $id;
                    $app->error('Failed to update!');
                } else {
                    if (($id = $this->_tbl->insert($rec)) !== false) return $id;
                    $app->error('Failed to insert!');
                }
            }
        }
        $app->error('Input data is not valid!');
    }

    public function setFields($fields)
    {
        $kvPairs = array();
        foreach ($fields as $key => $field) {
            $this->_modifyOptions[$key] = isset($field['modify']) ? $field['modify'] : 'required';
            if ($field === '') continue; // provided
            $this->_editFormFields[$key] = isset($field['edit']) ? $field['edit'] : array();
            if (isset($field['panel'])) {
                $v0 = $field['panel'];
                if (is_string($v0)) {
                    $v = '"' . $v0 . '"';
                } else {
                    $v = json_encode($v0);
                }
            } else {
                $v = '""';
            }
            if (isset($field['panelCallback'])) {
                $v = '{ def: ' . $v . ', callback: ' . $field['panelCallback'] . ' }';
            }
            $kvPairs[] = '"' . $key . '": ' . $v;
        }
        $this->_editPanelKeys = '{' . join(', ', $kvPairs) . '}';
        return $this;
    }

    public function doModify($data = null)
    {
        if (!$data) {
            if (!empty($_POST)) $data = $_POST;
        }
        if ($data) {
            $this->modify($data, $this->_modifyOptions);
        }
        return $this;
    }

    public function initView(
        $frontJs,
        $dataset = null,
        $encOptions = JSON_UNESCAPED_UNICODE
    ) {
        $app = Sys::app();
        $app->addScript('db_table');
        $app->addScript($frontJs);
        if ($app->canEdit) {
            $app->addScript('db_edit_panel');
        }
        if (!isset($dataset)) {
            $dataset = $this->tbl->select();
        }
        $app->addGlobalData($dataset, self::DATASET_VAR_NAME, $encOptions);
        return $this;
    }

    public function showEdit($title = null, $options = array())
    {
        if (!Sys::app()->canEdit) return $this;
        if (!$title) $title = 'Edit';
        if (empty($options['editFormName'])) {
            $options['editFormName'] = '__edit_form__';
        }
        if (empty($options['buttonsBoxId'])) {
            $options['buttonsBoxId'] = '__edit_form_buttons__';
        }
        Sys::render('db_view_edit', array(
            'editFormTitle' => $title,
            'editFormFields' => $this->_editFormFields,
            'editPanelKeys' => $this->_editPanelKeys,
            'editPanelOptions' => $options,
        ));
        return $this;
    }

    public function showData()
    {
        Sys::render('db_view', array(
            'dataSetVarName' => self::DATASET_VAR_NAME,
        ));
        return $this;
    }
}
