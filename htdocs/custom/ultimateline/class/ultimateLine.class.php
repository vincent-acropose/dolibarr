<?php

/* Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013 	   Philippe Grand       <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/ultimateline/class/ultimateLine.class.php
 *      \ingroup    ultimateline
 *      \class      ultimateLine
 *      \brief      Class to manage ultimateLine
 */
class UltimateLine extends CommonObject
{

    const SERVICE_TYPE_RATEONPRICE = 0;
	const SERVICE_TYPE_VALUEONPRICE = 1;
    const NAMINGTYPE_ONLYLABELS = 0;
    const NAMINGTYPE_ONLYREFERENCE = 1;
    const NAMINGTYPE_BOTH = 2;

    public $element='ultimateline';			//!< Id that identify managed objects
    public $table_element = 'ultimateline'; //!< Name of table without prefix where object is stored
	
    var $id;
    var $type;
    var $value;
    var $lines;

    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }

    /**
     *      Create object into database
     *      @param      user        	User that create
     *      @param      notrigger	    0=launch triggers after, 1=disable triggers
     *      @return     int         	<0 if KO, Id of created object if OK
     */
    function create(User $user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ultimateline(";
        $sql.= " fk_product_base";
        if (isset($this->type))
            $sql.= ", ultimate_service_type";
        if (isset($this->value))
            $sql.= ", ultimate_service_value";
        $sql.= ") VALUES (";
        $sql.= $this->id;
        if (isset($this->type))
            $sql.= ", " . $this->type;
        if (isset($this->type))
            $sql.= ", " . $this->value;
        $sql.= ")";

        $this->db->begin();

        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);

        if (!$resql)
        {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }


        // Commit or rollback
        if ($error)
        {
            foreach ($this->errors as $errmsg)
            {
                dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else
        {
            $this->db->commit();
            return $this->id;
        }
    }

    function create_lines(Array $servicesIdsToAdd, User $user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;
	        
        $insertValues = array();
        foreach ($servicesIdsToAdd as $serviceId)
            $insertValues[] = '(' . $this->id . ',' . $serviceId . ')';
        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ultimateline_association(";
        $sql.= " fk_product_base";
        $sql.= ", fk_product_target";
        $sql.= ") VALUES " . implode(',', $insertValues);

        $this->db->begin();

        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);

        if (!$resql)
        {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error)
        {

            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . 'ultimateline');

            if (!$notrigger)
            {
               
            }
        }

        // Commit or rollback
        if ($error)
        {
            foreach ($this->errors as $errmsg)
            {
                dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else
        {
            $this->db->commit();
            return $this->id;
        }
    }

    /**
     *    Load object in memory from database
     *    @param      id          id object
     *    @return     int         <0 if KO, >0 if OK
     */
    function fetch($idService)
    {
        global $langs;
        $sql = "SELECT";
        $sql.= " fk_product_base";
        $sql.= ", ultimate_service_type";
        $sql.= ", ultimate_service_value";

        $sql.= " FROM " . MAIN_DB_PREFIX . "ultimateline";
        $sql.= " WHERE fk_product_base = " . $idService;

        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->fk_product_base;
                $this->type = $obj->ultimate_service_type;
                $this->value = $obj->ultimate_service_value;
            }
            $this->db->free($resql);

            return 1;
        } else
        {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
            return -1;
        }
    }

    public function fetch_lines()
    {
        global $langs;

        $sql = "SELECT ";
        $sql.= " fk_product_target";

        $sql.= " FROM " . MAIN_DB_PREFIX . "ultimateline_association";

        $sql.= " WHERE fk_product_base = " . $this->id;

        dol_syslog(get_class($this) . "::fetch_lines sql=" . $sql, LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);

            $this->lines = array();

            if ($num)
            {
                $ii = 0;
                while ($ii < $num) {
                    $obj = $this->db->fetch_object($resql);

                    $this->lines[] = $obj->fk_product_target;

                    $ii++;
                }
                $this->db->free($resql);
            }

            return 1;
        } else
        {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch_lines " . $this->error, LOG_ERR);
            return -1;
        }
    }
    
    /**
     * Associate all available product
     */
    public function associateAllProduct() {
    	
    	global $conf, $langs;
    	$error = 0;
    	
    	$this->db->begin();
    	
    	$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'ultimateline_association WHERE fk_product_base='.$this->id;
    	dol_syslog(get_class($this) . "::associateAllProduct sql=" . $sql, LOG_DEBUG);
    	$resql = $this->db->query($sql);
    	if (!$resql)
    	{
    		$error++;
    		
    	}
    	
    	if (empty($error)) {
    		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "ultimateline_association(";
    		$sql.= " fk_product_base";
    		$sql.= ", fk_product_target)";
    		$sql.= " SELECT ".$this->id.", p.rowid FROM " . MAIN_DB_PREFIX . "product as p";
    		$sql.= " WHERE p.rowid <> ".$this->id;
    		
    		$resql = $this->db->query($sql);
    		dol_syslog(get_class($this) . "::associateAllProduct sql=" . $sql, LOG_DEBUG);
    		if (!$resql)
    		{
    			$error++;
    		}
    	}
    	
    	if (empty($error)) {
    		$this->db->commit();
    		return 1;
    	}
    	else
    	{
    		$this->error = "Error " . $this->db->lasterror();
    		dol_syslog(get_class($this) . "::associateAllProduct " . $this->error, LOG_ERR);
    		$this->db->rollback();
    		return -1;
    	}
    	
    	
    }

    /**
     *      Update object into database
     *      @param      user        	User that modify
     *      @param      notrigger	    0=launch triggers after, 1=disable triggers
     *      @return     int         	<0 if KO, >0 if OK
     */
    function update($user = 0, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;
     
        $sql = "UPDATE " . MAIN_DB_PREFIX . "ultimateline SET";
        $sql.= " ultimate_service_type = " . $this->type;
        $sql.= ", ultimate_service_value = " . $this->value;

        $sql.= " WHERE fk_product_base = " . $this->id;

        $this->db->begin();

        dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql)
        {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error)
        {
            if (!$notrigger)
            {
                
            }
        }

        // Commit or rollback
        if ($error)
        {
            foreach ($this->errors as $errmsg)
            {
                dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else
        {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *   Delete object in database
     * 	 @param     user        	User that delete
     *   @param     notrigger	    0=launch triggers after, 1=disable triggers
     *   @return	int				<0 if KO, >0 if OK
     */
    function delete($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        $this->db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ultimateline";
        $sql.= " WHERE fk_product_base =" . $this->id;

        if (!$this->delete_lines())
            $error++;

        dol_syslog(get_class($this) . "::delete sql=" . $sql);
        $resql = $this->db->query($sql);
        if (!$resql)
        {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error)
        {
            if (!$notrigger)
            {
                
            }
        }

        // Commit or rollback
        if ($error)
        {
            foreach ($this->errors as $errmsg)
            {
                dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else
        {
            $this->db->commit();
            return 1;
        }
    }

    public function delete_lines(Array $multipleIds = array(), $targetId = '')
    {
        global $conf, $langs;
        $error = 0;

        $this->db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ultimateline_association";
        if (!$targetId)
        {
            $sql.= " WHERE fk_product_base =" . $this->id;
            if (!empty($multipleIds))
                $sql.= " AND fk_product_target IN (" . implode(',', $multipleIds) . ")";
        } else
            $sql.= " WHERE fk_product_target =" . $targetId;
        dol_syslog(get_class($this) . "::delete_lines sql=" . $sql);
        $resql = $this->db->query($sql);
        if (!$resql)
        {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error)
        {
            if (!$notrigger)
            {
                
            }
        }

        // Commit or rollback
        if ($error)
        {
            foreach ($this->errors as $errmsg)
            {
                dol_syslog(get_class($this) . "::delete_lines " . $errmsg, LOG_ERR);
                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else
        {
            $this->db->commit();
            return 1;
        }
    }

    /**
     * 		Load an object from its id and create a new one in database
     * 		@param      fromid     		Id of object to clone
     * 	 	@return		int				New id of clone
     */
    function createFromClone($fromid)
    {
        global $user, $langs;

        $error = 0;

        $object = new Stockmove($this->db);

        $this->db->begin();

        // Load source object
        $object->fetch($fromid);
        $object->id = 0;
        $object->statut = 0;

        // Clear fields
        // ...
        // Create clone
        $result = $object->create($user);

        // Other options
        if ($result < 0)
        {
            $this->error = $object->error;
            $error++;
        }

        if (!$error)
        {
            
        }

        // End
        if (!$error)
        {
            $this->db->commit();
            return $object->id;
        } else
        {
            $this->db->rollback();
            return -1;
        }
    }

    public function getTypes()
    {
        global $langs;
        return array(
                self::SERVICE_TYPE_RATEONPRICE => $langs->trans('RateOnPrice'),
				self::SERVICE_TYPE_VALUEONPRICE => $langs->trans('ValueOnPrice')
        );
    }
    
    public function getUltimatesData()
    {
        global $langs;

        $sql = "SELECT ";
        $sql.= "  fk_product_base";
        $sql.= ", ultimate_service_type";
        $sql.= ", ultimate_service_value";

        $sql.= " FROM " . MAIN_DB_PREFIX . "ultimateline";


        dol_syslog(get_class($this) . "::getUltimatesData sql=" . $sql, LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $data = array();

            if ($num)
            {
                $ii = 0;
                while ($ii < $num) {
                    $obj = $this->db->fetch_object($resql);

                    $data[$obj->fk_product_base] = array(
                            'type' => $obj->ultimate_service_type,
                            'value' => $obj->ultimate_service_value
                    );
                    
                    $ii++;
                }
                $this->db->free($resql);
            }
            return $data;
        } else
        {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::getUltimatesData " . $this->error, LOG_ERR);
            return -1;
        }
    }

    public function getUltimatesByTargets()
    {
        global $langs;

        $sql = "SELECT ";
        $sql.= "  fl.fk_product_base";
        $sql.= ", fla.fk_product_target";

        $sql.= " FROM " . MAIN_DB_PREFIX . "ultimateline as fl";

        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "ultimateline_association as fla";
        $sql.= " ON fl.fk_product_base = fla.fk_product_base";

        dol_syslog(get_class($this) . "::getUltimatesByTargets sql=" . $sql, LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $ultimatesByTargets = array();

            if ($num)
            {
                $ii = 0;
                while ($ii < $num) {
                    $obj = $this->db->fetch_object($resql);

//                    $data[$obj->fk_product_base]['type'] = $obj->ultimate_service_type;
//                    $data[$obj->fk_product_base]['value'] = $obj->ultimate_service_value;
//                    if (isset($obj->fk_product_target))
//                        $data[$obj->fk_product_base]['targets'][] = $obj->fk_product_target;
                    if (isset($obj->fk_product_target))
                        $ultimatesByTargets[$obj->fk_product_target]['ultimates'][] = $obj->fk_product_base;

                    $ii++;
                }
                $this->db->free($resql);
            }
            return $ultimatesByTargets;
        } else
        {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::getUltimatesByTargets " . $this->error, LOG_ERR);
            return -1;
        }
    }

    public function getProducts()
    {
        global $langs;

        $sql = "SELECT ";
        $sql.= " pt.rowid";
        $sql.= ", pt.label";
        $sql.= ", pt.ref";
        $sql.= ", pt.fk_product_type";
        $sql.= ", fl.fk_product_base";


        $sql.= " FROM " . MAIN_DB_PREFIX . "product as pt";

        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "ultimateline as fl";
        $sql.= " ON pt.rowid = fl.fk_product_base";

        dol_syslog(get_class($this) . "::getProducts sql=" . $sql, LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $usableServices = array('labels' => array(), 'references' => array());
            $ultimateServices = array('labels' => array(), 'references' => array());
            $products = array('labels' => array(), 'references' => array());

            if ($num)
            {
                $ii = 0;
                while ($ii < $num) {
                    $obj = $this->db->fetch_object($resql);

                    if ($obj->fk_product_type == 1)
                    {
                        if ($obj->fk_product_base != null)
                        {
                            $ultimateServices['labels'][$obj->rowid] = $obj->label;
                            $ultimateServices['references'][$obj->rowid] = $obj->ref;
                        } else
                        {
                            $usableServices['labels'][$obj->rowid] = $obj->label;
                            $usableServices['references'][$obj->rowid] = $obj->ref;

                            $products['labels'][$obj->rowid] = $obj->label;
                            $products['references'][$obj->rowid] = $obj->ref;
                        }
                    } else
                    {
                        $products['labels'][$obj->rowid] = $obj->label;
                        $products['references'][$obj->rowid] = $obj->ref;
                    }
                    $ii++;
                }
                $this->db->free($resql);
            }

            // Ultimates
            $ultimateServicesEmpty[-1] = '';
            if (empty($ultimateServices['labels']))
            {
                $ultimateServicesEmpty[-1] = $langs->trans('NoServiceAvailable');
            }
            $ultimateServices['labels'] = $ultimateServicesEmpty + $ultimateServices['labels'];
            $ultimateServices['references'] = $ultimateServicesEmpty + $ultimateServices['references'];

            // Usables
            $usableServicesEmpty[-1] = '';
            if (empty($usableServices['labels']))
            {
                $usableServicesEmpty[-1] = $langs->trans('NoServiceAvailable');
            }
            $usableServices['labels'] = $usableServicesEmpty + $usableServices['labels'];
            $usableServices['references'] = $usableServicesEmpty + $usableServices['references'];

            // Products
            $productsEmpty[-1] = '';
            if (empty($products['labels']))
            {
                $productsEmpty[-1] = $langs->trans('NoProductAvailable');
            }
            $products['labels'] = $productsEmpty + $products['labels'];
            $products['references'] = $productsEmpty + $products['references'];

            return array(
                    'ultimate_services' => $ultimateServices,
                    'usable_services' => $usableServices,
                    'products' => $products
            );
        } else
        {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::getProducts " . $this->error, LOG_ERR);
            return -1;
        }
    }

    public function getUsableServices($namingType = self::NAMINGTYPE_BOTH)
    {
        $products = $this->getProducts();

        switch ($namingType)
        {
            case self::NAMINGTYPE_BOTH:
                return $products['usable_services'];
                break;
            case self::NAMINGTYPE_ONLYREFERENCE:
                return $products['usable_services']['references'];
                break;
            case self::NAMINGTYPE_ONLYLABELS:
                return $products['usable_services']['labels'];
                break;
        }
    }

    public function getUltimateServices($namingType = self::NAMINGTYPE_BOTH)
    {
        $products = $this->getProducts();

        switch ($namingType)
        {
            case self::NAMINGTYPE_BOTH:
                return $products['ultimate_services'];
                break;
            case self::NAMINGTYPE_ONLYREFERENCE:
                return $products['ultimate_services']['references'];
                break;
            case self::NAMINGTYPE_ONLYLABELS:
                return $products['ultimate_services']['labels'];
                break;
        }
    }

    public function getTargetProducts($namingType = self::NAMINGTYPE_BOTH)
    {
        $products = $this->getProduct();

        switch ($namingType)
        {
            case self::NAMINGTYPE_BOTH:
                return $products['products'];
                break;
            case self::NAMINGTYPE_ONLYREFERENCE:
                return $products['products']['references'];
                break;
            case self::NAMINGTYPE_ONLYLABELS:
                return $products['products']['labels'];
                break;
        }
    }

}

?>
