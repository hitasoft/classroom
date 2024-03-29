<?php
/**
 * Class represents records from table saved_form
 * {autogenerated}
 * @property int $saved_form_id 
 * @property string $title 
 * @property string $comment 
 * @property string $code 
 * @property string $fields 
 * @property string $tpl 
 * @property string $type 
 * @property string $default_for 
 * @see Am_Table
 * @package Am_SavedForm
 */
class SavedForm extends Am_Record 
{
    // default_for field values (set)
    const D_SIGNUP  = 'signup';
    const D_MEMBER  = 'member';
    const D_CART    = 'cart';
    const D_AFF     = 'aff';
    const D_PROFILE = 'profile';
    
    /// type values
    const T_SIGNUP  = 'signup';
    const T_CART    = 'cart';
    const T_PROFILE = 'profile';
    
    public function getTypeDef()
    {
        return $this->getTable()->getTypeDef($this->type);
    }
    public function isSignup()
    {
        $typeDef = $this->getTypeDef();
        return isset($typeDef['isSignup']) && $typeDef['isSignup'];
    }
    public function isCart()
    {
        return $this->type == self::T_CART;
    }
    public function getDefaultFor()
    {
        return array_filter(explode(',',$this->default_for));
    }
    public function setDefaultFor(array $values)
    {
        return $this->default_for = implode(',', array_unique(array_filter(array_map('filterId', $values))));
    }
    public function addDefaultFor($d)
    {
        $def = $this->getDefaultFor();
        $def[] = $d;
        $this->setDefaultFor($def);
        return $this;
    }
    public function delDefaultFor($d)
    {
        $def = $this->getDefaultFor();
        array_remove_value($def, $d);
        $this->setDefaultFor($def);
        return $this;
    }
    public function isDefault($dConst)
    {
        return in_array($dConst, $this->getDefaultFor());
    }
    public function getUrl($baseUrl)
    {
        $type = $this->getTypeDef();
        if (empty($type['urlTemplate']))
            return;
        if (is_array($type['urlTemplate']))
            $add = call_user_func($type['urlTemplate'], $this);
        else
            $add = $type['urlTemplate'];
        return $baseUrl . $add;
    }
    /** @return Am_Form_Bricked */
    public function createForm()
    {
        $type = $this->getTypeDef();
        if (!$type['class']) throw new Am_Exception("Could not instantiate form - empty class in typeDef");
        return new $type['class'];
    }
    public function generateCode()
    {
        do {
            $this->code = $this->getDi()->app->generateRandomString(rand(8,9));
        } while ($this->getTable()->findFirstByCode($this->code));
    }
    function getFields()
    {
        return (array)Am_Controller::decodeJson($this->fields);
    }
    function setFields(array $fields)
    {
        $this->fields = Am_Controller::getJson($fields);
    }
    /** @return array of Am_Form_Brick */
    function getBricks()
    {
        $ret = array();
        foreach ($this->getFields() as $brickConfig)
        {
            if (strpos($brickConfig['id'],'PageSeparator')===0) continue;
            $b = Am_Form_Brick::createFromRecord($brickConfig);
            if (!$b) continue;
            $ret[] = $b;
        }

        $event = new Am_Event(Am_Event::SAVED_FORM_GET_BRICKS, array(
            'type' => $this->type,
            'code' => $this->code,
            'savedForm' => $this
        ));
        $event->setReturn($ret);
        $this->getDi()->hook->call($event);

        $ret = $event->getReturn();
        foreach ($ret as $brick)
            $brick->init();

        return $ret;
    }

    function isSingle()
    {
        $typeDef = $this->getTypeDef();
        return isset($typeDef['isSingle']) && $typeDef['isSingle'];
    }

    function canDelete()
    {
        if (!$this->type) return true;
        $typeDef = $this->getTypeDef();
        if (!empty($typeDef['noDelete'])) return false;
        if (!empty($this->default_for)) return false;
        return true;
    }
    public function setDefaultBricks()
    {
        $value = array();
        foreach ($this->createForm()->getDefaultBricks() as $brick)
            $value[] = $brick->getRecord();
        $this->setFields($value);
        return $this;
    }

    public function setDefaults()
    {
        if (empty($this->type))
            throw new Am_Exception_InternalError("Error in ".__METHOD__." could not set defaults without type");
        
        $typeDef = $this->getTypeDef();
        
        if (!empty($typeDef['generateCode']) && empty($this->code))
            $this->generateCode();
        
        if (empty($this->title) && !empty($typeDef['defaultTitle']))
            $this->title = $typeDef['defaultTitle'];
        
        if (empty($this->comment) && !empty($typeDef['defaultComment']))
            $this->comment = $typeDef['defaultComment'];
        
        $this->setDefaultBricks();
        return $this;
    }
    
    function findBrickConfigs($class, $id = null)
    {
        $ret = array();
        foreach ($this->getFields() as $row)
        {
            if (empty($row['class']) || ($row['class'] != $class)) continue;
            if (($id === null) || ($row['id'] == $id)) $ret[] = $row;
        }
        return $ret;
    }
    function addBrickConfig($row)
    {
        $fields = $this->getFields();
        $fields[] = $row;
        $this->setFields($fields);
        return $this;
    }
    function removeBrickConfig($class, $id = null)
    {
        $fields = $this->getFields();
        $count = 0;
        foreach ($fields as $k => $row)
        {
            if ($row['class'] != $class) continue;
            if (($id !== null) && ($row['id'] == $id)) 
            {
                $count++;
                unset($fields[$k]);
            }
        }
        if ($count)
            $this->setFields($fields);
        return $this;
    }
    /**
     * @return array|null
     */
    function findBrickById($id)
    {
        $fields = $this->getFields();
        foreach ($fields as $k => $row)
        {
            if (($id !== null) && ($row['id'] == $id)) 
            {
                return $row;
            }
        }
    }
} 

/**
 * @package Am_SavedForm
 */
class SavedFormTable extends Am_Table {
    protected $_key = 'saved_form_id';
    protected $_table = '?_saved_form';
    protected $_recordClass = 'SavedForm';
    
    protected $_eventCalled = false;
    
    private $types = array(
        SavedForm::T_SIGNUP => array(
            'type' => SavedForm::T_SIGNUP,
            'class' => 'Am_Form_Signup',
            'title' => 'Signup Form',
            'defaultTitle' => 'Signup Form',
            'defaultComment' => 'customer signup/payment form',
            'isSignup' => true,
            'generateCode' => true,
            'urlTemplate'  => array('Am_Form_Signup', 'getSavedFormUrl'),
        ),
        SavedForm::T_PROFILE => array(
            'type' => SavedForm::T_PROFILE,
            'class' => 'Am_Form_Profile',
            'title' => 'Profile Form',
            'defaultTitle' => 'Customer Profile',
            'defaultComment' => 'customer profile form',
            'isSingle' => true,
            'isSignup' => false,
            'noDelete' => true,
            'urlTemplate'  => 'profile',
        ),
    );
    
    public function getOptions()
    {
        return $this->_db->selectCol("SELECT saved_form_id as ARRAY_KEY, concat(title, '(', comment, ')') FROM {$this->_table}");
    }
    
    /**
     * @param int $d_const one from SavedForm::D_xxx constant
     * @return SavedForm|null
     */
    function getDefault($dConst)
    {
        $row = $this->_db->selectRow("
            SELECT * FROM ?_saved_form 
            WHERE FIND_IN_SET(?, default_for) 
            LIMIT 1", 
            $dConst);
        if ($row) return $this->createRecord($row);
    }
    
    /**
     * @param string $type
     * @return SavedForm
     */
    function getByType($type)
    {
        return $this->findFirstByType($type);
    }
    
    function addTypeDef(array $typeDef)
    {
        $this->types[$typeDef['type']] = $typeDef;
        return $this;
    }
    /** @return array typedef 
     *  @throws exception if not found */
    function getTypeDef($type)
    {
        $this->runEventOnce();
        if (empty($this->types[$type])) 
            throw new Am_Exception_InternalError("Could not get typeDef for type [$type]");
        return $this->types[$type];
    }
    final public function getTypeDefs()
    {
        $this->runEventOnce();
        return $this->types;
    }
    
    protected function runEventOnce()
    {
        if (!$this->_eventCalled)
        {
            $this->_eventCalled = true;
            $this->getDi()->hook->call(Am_Event::SAVED_FORM_TYPES, 
                array('table' => $this));
        }
    }
    
    function getTypeOptions()
    {
        foreach ($this->getTypeDefs() as $t)
            $ret[$t['type']] = $t['title'];
        return $ret;
    }
    
    function setDefault($d, $saved_form_id)
    {
        switch ($d)
        {
            case SavedForm::D_SIGNUP:
            case SavedForm::D_MEMBER:
                $f = $this->load($saved_form_id);
                if ($f->type != SavedForm::T_SIGNUP)
                    throw new Am_Exception_InputError("Could not set default form - it has no 'signup' type");
                $f->addDefaultFor($d)->update();
                // now remove default from all other forms, there should not be too many
                foreach ($this->selectObjects("
                    SELECT * FROM ?_saved_form 
                    WHERE saved_form_id<>?d AND FIND_IN_SET(?, default_for)", $saved_form_id, $d) as $f)
                    $f->delDefaultFor($d)->update();
                break;
            default:
                throw new Am_Exception_InputError("Could not ".__METHOD__." for " . $d->$saved_form_id);
        }
    }
    
    public function getExistingTypes()
    {
        return $this->_db->selectCol("SELECT DISTINCT `type` FROM ?_saved_form WHERE type <> ''");
    }
}
