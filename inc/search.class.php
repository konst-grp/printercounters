<?php

/*
 -------------------------------------------------------------------------
 Printercounters plugin for GLPI
 Copyright (C) 2014 by the Printercounters Development Team.
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Printercounters.

 Printercounters is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Printercounters is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Printercounters. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------  */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginPrintercountersSearch
 * 
 * This class adds an alternative search in Ajax for the plugin
 * 
 * @package    Printercounters
 * @author     Ludovic Dupont
 */
class PluginPrintercountersSearch extends CommonDBTM {

   var $output_type    = search::HTML_OUTPUT;
   var $number         = 0;
   var $default_search = array();
   var $current_search = array();
   var $dataSearch     = array();
   var $input          = array();
   
  /**
   * Function get values
   * 
   * @param object $item
   * @param array $params
   */
   function manageHistoryGetValues($item, $params=array()) {

      // Set search values
      $p = $this->setSearchValues($item, $params);
      
      // Total Number of events
      $dataSearch = $p;
      unset($dataSearch['limit']);
      unset($dataSearch['start']);
      $this->dataSearch = $this->getHistoryFromDB($item, array('search' => $dataSearch));
      if (is_callable(array($item, 'countLines'))) {
         $this->number = $item->countLines($this);
      } else {
         $this->number = count($this->dataSearch);
      }
      
      // Get data
      $this->input = $this->getHistoryFromDB($item, array('search' => $p));
   }
   
  /**
   * Function set search values
   * 
   * @param type $params
   */
   function setSearchValues($item, $params){
            
      // Default values of parameters
      $this->default_search = $this->getDefaultSearch($item);
      
      $p['sort']  = $this->default_search['sort'];
      $p['order'] = $this->default_search['order'];
      $p['start'] = $this->default_search['start'];
      $p['limit'] = $this->default_search['limit'];
      
      foreach ($this->default_search['fields'] as $key => $val) {
         $p['contains'][]    = $val['value'];
         $p['searchtype'][]  = $val['searchtype'];
         $p['search_item'][] = $val['field'];
         $p['search_link'][] = $val['search_link'];
      }
      
      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }

      // Type of display
      if (isset($p["display_type"])) {
         $this->output_type = $p["display_type"];
         if ($this->output_type < 0) {
            $this->output_type = - $this->output_type;
         }
      }

      // Set current search parameters
      $this->current_search = $p;
      
      return $p;
   }
   
  /**
   * Function show search fields
   * 
   * @param object $item
   */
   function showHistoryGenericSearch($item) {
      global $CFG_GLPI;

      if (empty($this->default_search)) {
         $this->default_search = $this->getDefaultSearch($item);
      }
      
      if (empty($this->current_search)) {
         $this->current_search = $this->getDefaultSearch($item);
      }
         
      $itemtype = $item->getType();
      $ID = $item->getID();
      
      // Display search
      if ($this->output_type == search::HTML_OUTPUT) {
         echo "<form id='search_form".$item->rand."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'>";
         echo "<td>";
         echo "<div style='float:left;padding-top:8px;padding-bottom:8px;'>";
         // First line display add / delete images for normal and meta search items
         echo "<input type='hidden' disabled id='add_search_count".$item->rand."' name='add_search_count' value='".(count($this->default_search['fields'])-1)."'>";
         echo "<a href='javascript:void(0)' onClick = \"addSearchField('".$CFG_GLPI["root_doc"]."', 'search_line".$item->rand."', 'add_search_count".$item->rand."', 'search_form".$item->rand."');\">";
         echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title=\"".
         __('Add a search criterion')."\"></a>&nbsp;&nbsp;&nbsp;&nbsp;";

         echo "<a href='javascript:void(0)' onClick = \"deleteSearchField('search_line".$item->rand."', 'add_search_count".$item->rand."');\">";
         echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/moins.png\" alt='-' title=\"".
         __('Delete a search criterion')."\"></a>&nbsp;&nbsp;&nbsp;&nbsp;";
         echo "</div>";

         // Display link item
         foreach ($this->default_search['fields'] as $key => $val) {
            $this->addSearchField($key, $item, $val);
         }

         echo "</td>";

         // Submit 
         echo "<td class='center'>";
         echo "<input type='button' onClick = \"initSearch('".$CFG_GLPI["root_doc"]."', 'search_form".$item->rand."', 'history_showForm".$item->rand."', '".$this->current_search['start']."', '".$this->current_search['limit']."', '".$this->current_search['order']."', '".$this->current_search['sort']."');\" value='".__('Search')."' class='submit'>";
         echo "<input type='hidden' name='itemtype' value='".$itemtype."'>";
         echo "<input type='hidden' name='id' value='".$ID."'>";
         echo "<input type='hidden' name='item' value='".Toolbox::prepareArrayForInput(serialize($item))."'>";
         echo "<a href='javascript:void(0)' onClick = \"resetSearchField('".$CFG_GLPI['root_doc']."', 'history_showSearch".$item->rand."', 'search_form".$item->rand."', 'history_showForm".$item->rand."', '".$this->current_search['start']."', '".$this->current_search['limit']."', '".$this->current_search['order']."', '".$this->current_search['sort']."');\">";
         echo "&nbsp;&nbsp;<img title=\"".__('Blank')."\" alt=\"".__('Blank')."\" src='".
         $CFG_GLPI["root_doc"]."/pics/reset.png' class='calendrier'></a>";
         echo "</td>";
         echo "</tr></table>\n";

         Html::closeForm();
      }
   }
   
  /**
   * Function add search fields
   * 
   * @global type $CFG_GLPI
   * @param int $i
   * @param string $itemtype
   */
   function addSearchField($i, $item, $default_search_params=array()) {
      global $CFG_GLPI;

      $itemtype = $item->getType();
      
      if (empty($this->default_search)) {
         $default_search_params = $this->getDefaultSearch($item);
         $default_search_params = $default_search_params['fields'][0];
      }

      $default_search['search_link'] = '';
      $default_search['field']       = '';
      $default_search['value']       = '';
      $default_search['searchtype']  = '';
      
      foreach ($default_search_params as $key => $val) {
         $default_search[$key] = $val;
      }
       
      echo "<div id='search_line".$item->rand.$i."'>";
      echo "<table>";
      echo "<tr><td style='padding:0px 0px;'>";

      // Display link item
      if ($i > 0) {
         echo "<select id='search_link$i' name='search_link[$i]'>";
         $operators = array('AND', 'OR', 'AND NOT', 'OR NOT');
         foreach($operators as $val){
            $selected = ($default_search['search_link'] == $val) ?  'selected' : '';
            echo "<option $selected value='$val'>$val</option>\n";
         }
         echo "</select>&nbsp;";
      }

      // display select box to define search item
      echo "<select id='search_item$i' name='search_item[$i]' size='1'>";
      $options = Search::getCleanedOptions($itemtype);
      reset($options);
      
      $first_group = true;
      $str_limit   = 28;
      $nb_in_group = 0;
      $group       = '';

      foreach ($options as $key => $val) {
         // print groups
         if (!is_array($val)) {
            if (!$first_group) {
               $group .= "</optgroup>\n";
            } else {
               $first_group = false;
            }
            if ($nb_in_group) {
               echo $group;
            }
            $group = '';
            $nb_in_group = 0;

            $group .= "<optgroup label=\"".Toolbox::substr($val, 0, $str_limit)."\">";
         } else {
            if ((!isset($val['nosearch']) || $val['nosearch'] == false) && (!isset($val['nodisplay']) || $val['nodisplay'] == false)) {
               $nb_in_group++;
               $selected = ($default_search['field'] == $key)?  'selected' : '';
               $group .= "<option $selected title=\"".Html::cleanInputText($val["name"])."\" value='$key'";
               $group .= ">".Toolbox::substr($val["name"], 0, $str_limit)."</option>\n";
            } else {
               unset($options[$key]);
            }
         }
      }
      if (!$first_group) {
         $group .= "</optgroup>\n";
      }
      if ($nb_in_group) {
         echo $group;
      }
      echo "</select>&nbsp;";
      echo "</td>";

      echo "<td style='padding:0px 0px'>";
      echo "<div id='SearchSpan".$item->getType()."$i'>\n";
      if (isset($_POST['itemtype'])) $itemtype = $_POST['itemtype'];
      $_POST['itemtype']   = $item->getType();
      $_POST['num']        = $i;
      $_POST['field']      = $default_search['field'];
      $_POST['value']      = $default_search['value'];
      $_POST['searchtype'] = $default_search['searchtype'];
      include (GLPI_ROOT."/ajax/searchoption.php");
      $_POST['itemtype'] = !empty($itemtype) ? $itemtype : '';
      echo "</div>\n";


      $params = array('field'       => '__VALUE__',
                      'itemtype'    => $item->getType(),
                      'num'         => $i,
                      'value'       => '',
                      'searchtype'  => '');
      Ajax::updateItemOnSelectEvent("search_item$i", "SearchSpan".$item->getType()."$i", $CFG_GLPI["root_doc"]."/ajax/searchoption.php", $params);
      echo "</td></tr>";
      echo "</table></div>";
   }
   
  /**
   * Function set default search
   * 
   * @param object $item
   */
   function getDefaultSearch($item){
      
      $default_search   = array();
      $options          = Search::getCleanedOptions($item->getType());
      $fields_num       = array_keys($options);
      $default_search['fields'][] = array('field' => $fields_num[0], 'searchtype' => 'contains', 'value' => '', 'search_link' => '');
      $default_search['order'] = 'ASC';
      $default_search['sort']  = 1;
      $default_search['start'] = 0;
      $default_search['limit'] = $_SESSION['glpilist_limit'];
      
      if (is_callable(array($item, 'getDefaultSearch'))) {
         $custom_search = $item->getDefaultSearch($this);
         if (isset($custom_search['fields'])) {
            foreach($custom_search['fields'] as $key => $val){
               $default_search['fields'][$key] = $val;
            }
            unset($custom_search['fields']);
         }
         foreach ($custom_search as $key => $val) {
            $default_search[$key] = $val;
         }
      } 
      
      return $default_search;
   }
   
  /**
   * Function show each elments of search
   * 
   * @param type $item
   * @param type $options
   */
   function showSearch($item, $options=array()){

      $params['massiveaction'] = false;
      $params['display']       = true;
      $params['fixedDisplay']  = true;
      
      if (!empty($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }
      
      if (!isset($item->rand)) {
         $item->rand = mt_rand();
      }
      
      $item->massiveaction = $params['massiveaction'];
      $item->fixedDisplay  = $params['fixedDisplay'];
      
      // Init request and set data
      $this->manageHistoryGetValues($item, $params);
      
      if ($params['display']){
         // Display title
         $this->showTitle($item);

         // Show search bar
         echo "<div id='history_showSearch".$item->rand."'>";
         $this->showHistoryGenericSearch($item);
         echo "</div>";

         // Show list of items
         $canedit = ($item->canCreate() && $params['massiveaction'] && !(empty($this->input) && empty($this->dataSearch)));
         if ($canedit) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$item->rand);
            Html::showMassiveActions($item->getType(), array('fixed' => $params['fixedDisplay']));
         }

         echo "<div id='history_showForm".$item->rand."'>";
         $this->showHistory($item);
         echo "</div>";

         if ($canedit) {
            Html::showMassiveActions($item->getType(), array('fixed' => $params['fixedDisplay'], 
                                                             'ontop' => false));
            Html::closeForm();
         }
      }
   }
   
  /**
    * Display the search title
   * 
   * @param type $item
   */
   function showTitle($item){
      
      echo '<table class="tab_cadre_fixe">';
      echo '<tr><th>';
      if (is_callable(array($item, 'getSearchTitle'))) {
         echo $item->getSearchTitle($this);
      } else {
         echo $item::getTypeName();
      }
      echo '</th></tr>';
      echo '</table>';
   }

   /**
    * Print the history form
    * 
    * @param type $item
    * @return boolean
    */
   function showHistory($item) {
      global $CFG_GLPI;
      
      // validation des droits
      if (!$item->canView()) {
         return false;
      }

      $row_num = 1;
      $col_num = 1;
      $output  = '';
      
      $custom_display = false;
      if (is_callable(array($item, 'showSearchData'))) {
         $custom_display = true;
      } 
      
      $canedit = ($item->canCreate() && $item->massiveaction);

      if (empty($this->input) && empty($this->dataSearch)) {
         echo Search::showHeader($this->output_type, 0, 1, true);
         echo Search::showNewLine($this->output_type);
         echo Search::showItem($this->output_type,  __('No historical'), $col_num, $row_num, 'class="center"');
         echo Search::showEndLine($this->output_type);
         echo Search::showFooter($this->output_type, $item::getTypeName());
         
      } else {
         // Show pager
         if ($this->output_type == search::HTML_OUTPUT) {
            $this->setPrintPager($item, $_POST);
         }

         // Show headers
         echo Search::showHeader($this->output_type, count($this->input), 8, $item->fixedDisplay);
         echo Search::showNewLine($this->output_type);
         if ($canedit && $this->output_type == search::HTML_OUTPUT) {
            echo Search::showHeaderItem($this->output_type, Html::getCheckAllAsCheckbox('mass'.__CLASS__.$item->rand), $col_num);
         }
         $searchopt            = array();
         $itemtype             = $item->getType();
         $searchopt[$itemtype] = &Search::getOptions($itemtype);
         $globallinkto         = json_encode($this->current_search);
         ksort($searchopt[$itemtype]);
         foreach ($searchopt[$itemtype] as $num => $val) {
            if (isset($val['nodisplay']) && $val['nodisplay']) {
               continue;
            }
            $linkto = '';
            if (!isset($val['nosort']) || !$val['nosort']) {
               $linkto = "javascript:initSearch('".$CFG_GLPI["root_doc"]."', "
                                              . "'search_form".$item->rand."', "
                                              . "'history_showForm".$item->rand."', "
                                              . "'".$this->current_search['start']."', "
                                              . "'".$this->current_search['limit']."', "
                                              . "'".(($this->current_search['order'] == "ASC") ? "DESC" : "ASC")."', "
                                              . "'".$num."');";
            }
            echo Search::showHeaderItem($this->output_type, $val["name"], $col_num, $linkto, ($this->current_search['sort'] == $num), $this->current_search['order']);
         }
         echo Search::showEndLine($this->output_type);

         // Show custom data
         if ($custom_display) {
            $item->showSearchData($this);
            
         // Default data display
         } else {
            foreach ($this->input as $history) {
               $row_num++;
               $col_num = 1;
               
               // Show massive action checkbox
               $count   = 0;
               echo Search::showNewLine($this->output_type);
               if($canedit && $this->output_type == search::HTML_OUTPUT ){
                  foreach ($searchopt[$item->getType()] as $num => $val) {
                     if ($val['table'] == $item->getTable() && $val['field'] == 'id') {
                        echo "<td class='center' width='10'>";
                        Html::showMassiveActionCheckBox(__CLASS__, Search::giveItem($item->getType(), $num, $history, $count));
                        echo "</td>";
                     }
                     $count++;
                  }
               }
               
               // Show columns
               $count   = 0;
               foreach ($searchopt[$item->getType()] as $num => $val) {
                  if ((isset($val['nodisplay']) && $val['nodisplay']) || (isset($val['nosql']) && $val['nosql'])) {
                     continue;
                  }
                  echo Search::showItem($this->output_type, Search::giveItem($item->getType(), $num, $history, $count), $col_num, $row_num);   
                  $count++;
               }
               echo Search::showEndLine($this->output_type);
            }
         }
         
         echo Search::showFooter($this->output_type, self::getTypeName());
      }

      echo $output;
   }

  /**
   * Function construct query and get values
   * 
   * @global type $DB
   * @global type $CFG_GLPI
   * @param type $item
   * @param array $params
   * @return type
   */
   function getHistoryFromDB($item, array $params) {
      global $DB, $CFG_GLPI;

      $itemtype = $item->getType();

      // Default values of parameters
      $p                = array();
      $p['search_item'] = array();
      $p['contains']    = array();
      $p['search_link'] = array();
      $p['sort']        = '';
      $p['order']       = '';

      foreach ($params['search'] as $key => $val) {
         $p[$key] = $val;
      }
      
      $searchopt = array();
      $searchopt[$item->getType()] = &Search::getOptions($item->getType());
      
       // Get the items to display
      $toview = array();
      foreach ($searchopt[$item->getType()] as $key => $val) {
         if ((!isset($val['nosql']) || $val['nosql'] == false)) {
            $toview[] = $key;
         }
      }
      sort($toview);

      if (in_array('all', $p['search_item'])
          && !$CFG_GLPI['allow_search_all']) {
         Html::displayRightError();
      }
      if (in_array('view', $p['search_item'])
          && !$CFG_GLPI['allow_search_view']) {
         Html::displayRightError();
      }

      // Manage defautll seachtype value : for bookmark compatibility
      if (count($p['contains'])) {
         foreach ($p['contains'] as $key => $val) {
            if (!isset($p['searchtype'][$key])) {
               $p['searchtype'][$key] = 'contains';
            }
         }
      }

      $blacklist_tables = array();
      if (isset($CFG_GLPI['union_search_type'][$itemtype])) {
         $itemtable = $CFG_GLPI['union_search_type'][$itemtype];
         $blacklist_tables[] = getTableForItemType($itemtype);
      } else {
         $itemtable = getTableForItemType($itemtype);
      }

      // hack for AllAssets
      if (isset($CFG_GLPI['union_search_type'][$itemtype])) {
         $entity_restrict = true;
      } else {
         $entity_restrict = $item->isEntityAssign();
      }

      // Construct the request

      // 1 - SELECT
      // request currentuser for SQL supervision, not displayed
      $query = "SELECT ";
      // Add select for all toview item
      foreach ($toview as $key => $val) {
         $query .= self::addSelect($itemtype, $val, $key, 0);
      }
      
      if (!empty($itemtable)) {
         $query .= "`$itemtable`.`id` AS id ";
      }

      // 2 - FROM AND LEFT JOIN
      // Set reference table
      $query .= " FROM `$itemtable`";

      // Init already linked tables array in order not to link a table several times
      $already_link_tables = array();
      // Put reference table
      array_push($already_link_tables, $item->getTable());

      // Add default join
      $COMMONLEFTJOIN = Search::addDefaultJoin($item->getType(), $item->getTable(), $already_link_tables);
      $query .= $COMMONLEFTJOIN;

      // Search all case 
      foreach ($searchopt[$item->getType()] as $key => $val) {
         // Do not search on Group Name
         if (is_array($val) && (!isset($val['nosql']) || $val['nosql'] == false)) {
            if (!in_array($searchopt[$item->getType()][$key]["table"], $blacklist_tables)) {
               $query .= self::addLeftJoin($item->getType(), $item->getTable(), $already_link_tables,
                                          $searchopt[$item->getType()][$key]["table"],
                                          $searchopt[$item->getType()][$key]["linkfield"], 0, 0,
                                          $searchopt[$item->getType()][$key]["joinparams"]);
            }
         }
      }
      
      // 3 - WHERE
      $criteria = array();
      foreach ($p['search_item'] as $key => $search_item) {
         if (!empty($p['contains'][$key])) {
            $LINK = " ";
            $NOT = 0;
            $tmplink = "";

            if (is_array($p['search_link']) && isset($p['search_link'][$key])) {
               if (strstr($p['search_link'][$key], "NOT")) {
                  $tmplink = " ".str_replace(" NOT", "", $p['search_link'][$key]);
                  $NOT = 1;
               } else {
                  $tmplink = " ".$p['search_link'][$key];
               }
            } else {
               $tmplink = " AND ";
            }
            // Manage Link if not first item
            if (!empty($criteria)) {
               $LINK = $tmplink;
            }

            $criteria[$key] = self::addWhere($LINK, $NOT, $item->getType(), $p['search_item'][$key], $p['searchtype'][$key], $p['contains'][$key]);
         }
      }
      
      $query .= " WHERE 1";
      if (!empty($criteria)) {
         $query .= " AND ( ";
         foreach ($criteria as $value) {
            $query .= $value;
         }
         $query .= " )";
      }
      
      // Add item restrictions if needed
      if (is_callable(array($item, 'addRestriction'))) {
         $query .= " AND ".$item->addRestriction($this);
      }
      
      // 4- GROUP BY
      if (is_callable(array($item, 'addGroupBy'))) {
         $query .= " GROUP BY ".$item->addGroupBy($this);
      } else {
         //// 7 - Manage GROUP BY
         $GROUPBY = "";
         if (empty($GROUPBY)) {
            foreach ($toview as $key => $val) {
               if (!empty($GROUPBY)) {
                  break;
               }
               if (isset($searchopt[$itemtype][$val]["forcegroupby"])) {
                  $GROUPBY = " GROUP BY `$itemtable`.`id`";
               }
            }
         }
         $query .= $GROUPBY;
      }
      
      // 5 - ORDER
      if (isset($p['sort']) && isset($p['order'])) {
         foreach ($toview as $key => $val) {
            if ($p['sort'] == $val) {
               $query .= self::addOrderBy($itemtype, $p['sort'], $p['order'], $key);
            }
         }
      }
      
      // 6 - LIMIT
      if (isset($p['start']) && isset($p['limit'])) {
         if (is_callable(array($item, 'addLimit'))) {
            $query .= " LIMIT ".$item->addLimit($this);
         } else {
            $query .= " LIMIT ".intval($p['start']).",".intval($p['limit']);
         }
      }
      
      // Return results
      $result = $DB->query($query);
      $output = array();
      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $output[] = $data;
         }
      }

      return $output;
   }
   
   /**
    * Generic Function to add where to a request
    *
    * @param $link         link string
    * @param $nott         is it a negative search ?
    * @param $itemtype     item type
    * @param $ID           ID of the item to search
    * @param $searchtype   searchtype used (equals or contains)
    * @param $val          item num in the request
    * @param $meta         is a meta search (meta=2 in search.class.php) (default 0)
    *
    * @return select string
   **/
   static function addWhere($link, $nott, $itemtype, $ID, $searchtype, $val, $meta=0) {

      $searchopt = &Search::getOptions($itemtype);
      $table     = $searchopt[$ID]["table"];
      $field     = $searchopt[$ID]["field"];

      $inittable = $table;
      $addtable  = '';

      if ($table != 'asset_types'
          && ($table != getTableForItemType($itemtype))
          && ($searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table))) {
//         $addtable = "_".$searchopt[$ID]["linkfield"];
         $table   .= $addtable;
      }
 
      if (isset($searchopt[$ID]['joinparams'])) {
         $complexjoin = Search::computeComplexJoinID($searchopt[$ID]['joinparams']);

         if (!empty($complexjoin)) {
//            $table .= "_".$complexjoin;
         }
      }

      if ($meta
          && (getTableForItemType($itemtype) != $table)) {
         $table .= "_".$itemtype;
      }

      // Hack to allow search by ID on every sub-table
      if (preg_match('/^\$\$\$\$([0-9]+)$/',$val,$regs)) {
         return $link." (`$table`.`id` ".($nott?"<>":"=").$regs[1]." ".
                         (($regs[1] == 0)?" OR `$table`.`id` IS NULL":'').") ";
      }

      // Preparse value
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "datetime" :
            case "date" :
            case "date_delay" :
               $force_day = true;
               if ($searchopt[$ID]["datatype"] == 'datetime') {
                  $force_day = false;
               }
               if (strstr($val,'BEGIN') || strstr($val,'LAST')) {
                  $force_day = true;
               }

               $val = Html::computeGenericDateTimeSearch($val, $force_day);

               break;
         }
      }
      switch ($searchtype) {
         case "contains" :
            $SEARCH = Search::makeTextSearch($val, $nott);
            break;

         case "equals" :
            if ($nott) {
               $SEARCH = " <> '$val'";
            } else {
               $SEARCH = " = '$val'";
            }
            break;

         case "notequals" :
            if ($nott) {
               $SEARCH = " = '$val'";
            } else {
               $SEARCH = " <> '$val'";
            }
            break;

         case "under" :
            if ($nott) {
               $SEARCH = " NOT IN ('".implode("','",getSonsOf($inittable, $val))."')";
            } else {
               $SEARCH = " IN ('".implode("','",getSonsOf($inittable, $val))."')";
            }
            break;

         case "notunder" :
            if ($nott) {
               $SEARCH = " IN ('".implode("','",getSonsOf($inittable, $val))."')";
            } else {
               $SEARCH = " NOT IN ('".implode("','",getSonsOf($inittable, $val))."')";
            }
            break;

      }

      // Plugin can override core definition for its type
      if ($plug = isPluginItemType($itemtype)) {
         $function = 'plugin_'.$plug['plugin'].'_addWhere';
         if (function_exists($function)) {
            $out = $function($link,$nott,$itemtype,$ID,$val);
            if (!empty($out)) {
               return $out;
            }
         }
      }
      
      switch ($inittable.".".$field) {
//          case "glpi_users_validation.name" :
         case "glpi_users.name" :
            if ($itemtype == 'User') { // glpi_users case / not link table
               if (in_array($searchtype, array('equals', 'notequals'))) {
                  return " $link `$table`.`id`".$SEARCH;
               }
               return Search::makeTextCriteria("`$table`.`$field`", $val, $nott, $link);
            }
            if ($_SESSION["glpinames_format"] == User::FIRSTNAME_BEFORE) {
               $name1 = 'firstname';
               $name2 = 'realname';
            } else {
               $name1 = 'realname';
               $name2 = 'firstname';
            }

            if (in_array($searchtype, array('equals', 'notequals'))) {
               return " $link (`$table`.`id`".$SEARCH.
                               (($val == 0)?" OR `$table`.`id` IS NULL":'').') ';
            }
            $toadd   = '';

            $tmplink = 'OR';
            if ($nott) {
               $tmplink = 'AND';
            }

            if (($itemtype == 'Ticket') || ($itemtype == 'Problem')) {
               if (isset($searchopt[$ID]["joinparams"]["beforejoin"]["table"])
                   && isset($searchopt[$ID]["joinparams"]["beforejoin"]["joinparams"])
                   && (($searchopt[$ID]["joinparams"]["beforejoin"]["table"] == 'glpi_tickets_users')
                       || ($searchopt[$ID]["joinparams"]["beforejoin"]["table"] == 'glpi_problems_users'))) {

                  $bj        = $searchopt[$ID]["joinparams"]["beforejoin"];
                  $linktable = $bj['table'].'_'.Search::computeComplexJoinID($bj['joinparams']);
                  //$toadd     = "`$linktable`.`alternative_email` $SEARCH $tmplink ";
                  $toadd     = Search::makeTextCriteria("`$linktable`.`alternative_email`", $val,
                                                      $nott, $tmplink);
               }
            }
            $toadd2 = '';
            if ($nott
                && ($val != 'NULL') && ($val != 'null')) {
               $toadd2 = " OR `$table`.`$field` IS NULL";
            }
            return $link." (((`$table`.`$name1` $SEARCH
                            $tmplink `$table`.`$name2` $SEARCH
                            $tmplink `$table`.`$field` $SEARCH
                            $tmplink CONCAT(`$table`.`$name1`, ' ', `$table`.`$name2`) $SEARCH )
                            $toadd2) $toadd)";


         case "glpi_groups.completename" :
            if ($val == 'mygroups') {
               switch ($searchtype) {
                  case 'equals' :
                     return " $link (`$table`.`id` IN ('".implode("','",$_SESSION['glpigroups'])."')) ";
                     break;

                  case 'notequals' :
                     return " $link (`$table`.`id` NOT IN ('".implode("','",$_SESSION['glpigroups'])."')) ";
                     break;
                     
                  case 'under' :
                     $groups = $_SESSION['glpigroups'];
                     foreach ($_SESSION['glpigroups'] as $g) {
                        $groups += getSonsOf($inittable, $g);
                     }
                     $groups = array_unique($groups);
                     return " $link (`$table`.`id` IN ('".implode("','", $groups)."')) ";
                     break;
                     
                  case 'notunder' :
                     $groups = $_SESSION['glpigroups'];
                     foreach ($_SESSION['glpigroups'] as $g) {
                        $groups += getSonsOf($inittable, $g);
                     }
                     $groups = array_unique($groups);
                     return " $link (`$table`.`id` NOT IN ('".implode("','", $groups)."')) ";
                     break;                     
               }
            }
            break;

         case "glpi_networkports.mac" :
            if ($itemtype == 'Computer') {
               return "$link (".Search::makeTextCriteria("`glpi_items_devicenetworkcards`.`mac`",
                                                       $val, $nott,'').
                              Search::makeTextCriteria("`$table`.`$field`", $val ,$nott, 'OR').")";
            }
            return Search::makeTextCriteria("`$table`.`$field`", $val, $nott, $link);

         case "glpi_infocoms.sink_type" :
            $ADD = "";
            if ($nott
                && ($val != 'NULL') && ($val != 'null')) {
               $ADD = " OR `$table`.`$field` IS NULL";
            }

            if (stristr($val,Infocom::getAmortTypeName(1))) {
               $val = 1;
            } else if (stristr($val,Infocom::getAmortTypeName(2))) {
               $val = 2;
            }

            if (is_int($val) && ($val > 0)) {
               if ($nott) {
                  return $link." (`$table`.`$field` <> '$val' ".
                                  $ADD." ) ";
               }
               return $link." (`$table`.`$field` = '$val' ".
                               $ADD." ) ";
            }
            break;

         case "glpi_contacts.completename" :
            if (in_array($searchtype, array('equals', 'notequals'))) {
               return " $link `$table`.`id`".$SEARCH;
            }
            if ($_SESSION["glpinames_format"] == User::FIRSTNAME_BEFORE) {
               $name1 = 'firstname';
               $name2 = 'name';
            } else {
               $name1 = 'name';
               $name2 = 'firstname';
            }

            $tmplink = 'OR';
            if ($nott) {
               $tmplink = 'AND';
            }
            return $link." (`$table`.`$name1` $SEARCH
                            $tmplink `$table`.`$name2` $SEARCH
                            $tmplink CONCAT(`$table`.`$name1`,' ',`$table`.`$name2`) $SEARCH) ";

         case "glpi_auth_tables.name" :
            $user_searchopt = Search::getOptions('User');
            $tmplink        = 'OR';
            if ($nott) {
               $tmplink = 'AND';
            }
            return $link." (`glpi_authmails".$addtable."_".
                              Search::computeComplexJoinID($user_searchopt[31]['joinparams'])."`.`name`
                           $SEARCH
                           $tmplink `glpi_authldaps".$addtable."_".
                              Search::computeComplexJoinID($user_searchopt[30]['joinparams'])."`.`name`
                           $SEARCH ) ";

         case "glpi_contracts.renewal" :
            $valid = Contract::getContractRenewalIDByName($val);
            if ($valid > 0) {
               return $link." `$table`.`$field`"."="."'$valid'";
            }
            return "";

         case "glpi_profiles.interface" :
            if (stristr(Profile::getInterfaceName('central'),$val)) {
               return $link." `$table`.`$field`='central'";
            }
            if (stristr(Profile::getInterfaceName('helpdesk'),$val)) {
               return $link." `$table`.`$field`='helpdesk'";
            }
            return "";

         case "glpi_ipaddresses.name" :
            $search  = array("/\&lt;/","/\&gt;/");
            $replace = array("<",">");
            $val     = preg_replace($search, $replace, $val);
            if (preg_match("/^\s*([<>])([=]*)[[:space:]]*([0-9\.]+)/",$val,$regs)) {
               if ($nott) {
                  if ($regs[1] == '<') {
                     $regs[1] = '>';
                  } else {
                     $regs[1] = '<';
                  }
               }
               $regs[1] .= $regs[2];
               return $link." (INET_ATON(`$table`.`$field`) ".$regs[1]." INET_ATON('".$regs[3]."')) ";
            }
//             return Search::makeTextCriteria("`$table`.`$field`", $val, $nott, $link);
            break;

         case "glpi_tickets.status" :
         case "glpi_problems.status" :
         case "glpi_changes.status" :
            if ($val == 'all') {
               return "";
            }
            $tocheck = array();
            if ($item = getItemForItemtype($itemtype)) {
               switch ($val) {
                  case 'process' :
                     $tocheck = $item->getProcessStatusArray();
                     break;

                  case 'notclosed' :
                     $tocheck = $item->getAllStatusArray();
                     foreach ($item->getClosedStatusArray() as $status) {
                        if (isset($tocheck[$status])) {
                           unset($tocheck[$status]);
                        }
                     }
                     $tocheck = array_keys($tocheck);
                     break;

                  case 'old' :
                     $tocheck = array_merge($item->getSolvedStatusArray(),
                                            $item->getClosedStatusArray());
                     break;

                  case 'notold' :
                     $tocheck = $item->getAllStatusArray();
                     foreach ($item->getSolvedStatusArray() as $status) {
                        if (isset($tocheck[$status])) {
                           unset($tocheck[$status]);
                        }
                     }
                     foreach ($item->getClosedStatusArray() as $status) {
                        if (isset($tocheck[$status])) {
                           unset($tocheck[$status]);
                        }
                     }
                     $tocheck = array_keys($tocheck);
                     break;
               }
            }
            if (count($tocheck) == 0) {
               $statuses = $item->getAllStatusArray();
               if (isset($statuses[$val])) {
                  $tocheck = array($val);
               }
            }

            if (count($tocheck)) {
               if ($nott) {
                  return $link." `$table`.`$field` NOT IN ('".implode("','",$tocheck)."')";
               }
               return $link." `$table`.`$field` IN ('".implode("','",$tocheck)."')";
            }
            break;

         case "glpi_tickets_tickets.tickets_id_1" :
            $tmplink = 'OR';
            $compare = '=';
            if ($nott) {
               $tmplink = 'AND';
               $compare = '<>';
            }
            $toadd2 = '';
            if ($nott
                && ($val != 'NULL') && ($val != 'null')) {
               $toadd2 = " OR `$table`.`$field` IS NULL";
            }

            return $link." (((`$table`.`tickets_id_1` $compare '$val'
                              $tmplink `$table`.`tickets_id_2` $compare '$val')
                             AND `glpi_tickets`.`id` <> '$val')
                            $toadd2)";

         case "glpi_tickets.priority" :
         case "glpi_tickets.impact" :
         case "glpi_tickets.urgency" :
         case "glpi_projects.priority" :
         case "glpi_projects.impact" :
         case "glpi_projects.urgency" :
            if (is_numeric($val)) {
               if ($val > 0) {
                  return $link." `$table`.`$field` = '$val'";
               }
               if ($val < 0) {
                  return $link." `$table`.`$field` >= '".abs($val)."'";
               }
               // Show all
               return $link." `$table`.`$field` >= '0' ";
            }
            return "";

         case "glpi_tickets.global_validation" :
         case "glpi_ticketvalidations.status" :
            $tocheck = array('none'     => array('none'),
                             'waiting'  => array('waiting'),
                             'rejected' => array('rejected'),
                             'accepted' => array('accepted'),
                             'can'      => array('none', 'accepted'),
                             'all'      => array('none', 'waiting', 'rejected', 'accepted'));
            if (isset($tocheck[$val])) {
               foreach ($tocheck[$val] as $key => $nval) {
                  $tocheck[$val][$key] = " `$table`.`$field` = '$nval' ";
               }
               return $link.'('.implode(' OR ', $tocheck[$val]).')';
            }
            if ($val == 'all') {
               return "";
            }
            break;

         case "glpi_ticketsatisfactions.type" :
            return $link." `$table`.`$field` = '$val' ";

         case "glpi_tickets.is_late" :
            return " $link IF(`$table$addtable`.`due_date` IS NOT NULL
                              AND (`$table$addtable`.`solvedate` > `$table$addtable`.`due_date`
                                   OR (`$table$addtable`.`solvedate` IS NULL
                                       AND `$table$addtable`.`due_date` < NOW())),
                              1, 0)
                     $SEARCH ";

      }

      //// Default cases

      // Link with plugin tables
      if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $inittable, $matches)) {
         if (count($matches) == 2) {
            $plug     = $matches[1];
            $function = 'plugin_'.$plug.'_addWhere';
            if (function_exists($function)) {
               $out = $function($link, $nott, $itemtype, $ID, $val);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }

      $tocompute = "`$table`.`$field`";
      if (isset($searchopt[$ID]["computation"])) {
         $tocompute = $searchopt[$ID]["computation"];
         $tocompute = str_replace("TABLE", "`$table`", $tocompute);
      }

      // Preformat items
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "itemtypename" :
               if (in_array($searchtype, array('equals', 'notequals'))) {
                  return " $link (`$table`.`$field`".$SEARCH.') ';
               }
               break;

            case "itemlink" :
               if (in_array($searchtype, array('equals', 'notequals'))) {
                  return " $link (`$table`.`id`".$SEARCH.') ';
               }
               break;

            case "datetime" :
            case "date" :
            case "date_delay" :
               if ($searchopt[$ID]["datatype"] == 'datetime') {
                  // Specific search for datetime
                  if (in_array($searchtype, array('equals', 'notequals'))) {
                     $val = preg_replace("/:00$/",'',$val);
                     $val = '^'.$val;
                     if ($searchtype == 'notequals') {
                        $nott = !$nott;
                     }
                     return Search::makeTextCriteria("`$table`.`$field`", $val, $nott, $link);
                  }
               }
               if ($searchtype == 'lessthan') {
                 $val = '<'.$val;
               }
               if ($searchtype == 'morethan') {
                 $val = '>'.$val;
               }
               if ($searchtype) {
                  $date_computation = $tocompute;
               }
               $search_unit = ' MONTH ';
               if (isset($searchopt[$ID]['searchunit'])) {
                  $search_unit = $searchopt[$ID]['searchunit'];
               }
               if ($searchopt[$ID]["datatype"]=="date_delay") {
                  $delay_unit = ' MONTH ';
                  if (isset($searchopt[$ID]['delayunit'])) {
                     $delay_unit = $searchopt[$ID]['delayunit'];
                  }
                  $add_minus = '';
                  if (isset($searchopt[$ID]["datafields"][3])) {
                     $add_minus = "-`$table`.`".$searchopt[$ID]["datafields"][3]."`";
                  }
                  $date_computation = "ADDDATE(`$table`.".$searchopt[$ID]["datafields"][1].",
                                               INTERVAL (`$table`.".$searchopt[$ID]["datafields"][2]."
                                                 $add_minus) $delay_unit)";
               }
               if (in_array($searchtype, array('equals', 'notequals'))) {
                  return " $link ($date_computation ".$SEARCH.') ';
               }
               $search  = array("/\&lt;/","/\&gt;/");
               $replace = array("<",">");
               $val     = preg_replace($search,$replace,$val);
               if (preg_match("/^\s*([<>=]+)(.*)/",$val,$regs)) {
                  if (is_numeric($regs[2])) {
                     return $link." $date_computation ".$regs[1]."
                            ADDDATE(NOW(), INTERVAL ".$regs[2]." $search_unit) ";
                  }
                  // ELSE Reformat date if needed
                  $regs[2] = preg_replace('@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@','\5-\3-\1',
                                          $regs[2]);
                  if (preg_match('/[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}/', $regs[2])) {
                     return $link." $date_computation ".$regs[1]." '".$regs[2]."'";
                  }
                  return "";
               }
               // ELSE standard search
               // Date format modification if needed
               $val = preg_replace('@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@','\5-\3-\1', $val);
               return Search::makeTextCriteria($date_computation, $val, $nott, $link);

            case "right" :
               if (($val == 'NULL') || ($val == 'null')) {
                  return $link." $tocompute IS ".($nott?'NOT':'')." NULL ";
               }
               return $link." $tocompute = '$val' ";

            case "bool" :
               if (!is_numeric($val)) {
                  if (strcasecmp($val,__('No')) == 0) {
                     $val = 0;
                  } else if (strcasecmp($val,__('Yes')) == 0) {
                     $val = 1;
                  }
               }
               // No break here : use number comparaison case

            case "number" :
            case "decimal" :
            case "timestamp" :
               $search  = array("/\&lt;/", "/\&gt;/");
               $replace = array("<", ">");
               $val     = preg_replace($search, $replace, $val);

               if (preg_match("/([<>])([=]*)[[:space:]]*([0-9]+)/", $val, $regs)) {
                  if ($nott) {
                     if ($regs[1] == '<') {
                        $regs[1] = '>';
                     } else {
                        $regs[1] = '<';
                     }
                  }
                  $regs[1] .= $regs[2];
                  return $link." ($tocompute ".$regs[1]." ".$regs[3].") ";
               }
               if (is_numeric($val)) {
                  if (isset($searchopt[$ID]["width"])) {
                     $ADD = "";
                     if ($nott
                         && ($val != 'NULL') && ($val != 'null')) {
                        $ADD = " OR $tocompute IS NULL";
                     }
                     if ($nott) {
                        return $link." ($tocompute < ".(intval($val) - $searchopt[$ID]["width"])."
                                        OR $tocompute > ".(intval($val) + $searchopt[$ID]["width"])."
                                        $ADD) ";
                     }
                     return $link." (($tocompute >= ".(intval($val) - $searchopt[$ID]["width"])."
                                      AND $tocompute <= ".(intval($val) + $searchopt[$ID]["width"]).")
                                     $ADD) ";
                  }
                  if (!$nott) {
                     return " $link ($tocompute = ".(intval($val)).") ";
                  }
                  return " $link ($tocompute <> ".(intval($val)).") ";
               }
               break;
         }
      }
   
      // Default case
      if (in_array($searchtype, array('equals', 'notequals','under', 'notunder'))) {
       
         if ((!isset($searchopt[$ID]['searchequalsonfield'])
               || !$searchopt[$ID]['searchequalsonfield'])
            && (($table != getTableForItemType($itemtype))
               || ($itemtype == 'AllAssets'))) {
            $out = " $link (`$table`.`id`".$SEARCH;
         } else {
            $out = " $link (`$table`.`$field`".$SEARCH;
         }
         if ($searchtype == 'notequals') {
            $nott = !$nott;
         }
         // Add NULL if $val = 0 and not negative search
         // Or negative search on real value
         if ((!$nott && ($val == 0))
             || ($nott && ($val != 0))) {
            $out .= " OR `$table`.`id` IS NULL";
         }
         $out .= ')';
         return $out;
      }

      return Search::makeTextCriteria($tocompute,$val,$nott,$link);
   }
   
   /**
    * Generic Function to add ORDER BY to a request
    *
    * @param $itemtype  ID of the device type
    * @param $ID        field to add
    * @param $order     order define
    * @param $key       item number (default 0)
    *
    * @return select string
    *
   **/
   static function addOrderBy($itemtype, $ID, $order, $key=0) {
      global $CFG_GLPI;

      // Security test for order
      if ($order != "ASC") {
         $order = "DESC";
      }
      $searchopt = &Search::getOptions($itemtype);

      $table     = $searchopt[$ID]["table"];
      $field     = $searchopt[$ID]["field"];


      $addtable = '';

//      if (($table != getTableForItemType($itemtype))
//          && ($searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table))) {
//         $addtable .= "_".$searchopt[$ID]["linkfield"];
//      }

//      if (isset($searchopt[$ID]['joinparams'])) {
//         $complexjoin = self::computeComplexJoinID($searchopt[$ID]['joinparams']);
//
//         if (!empty($complexjoin)) {
//            $addtable .= "_".$complexjoin;
//         }
//      }

      if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
         return " ORDER BY ITEM_$key $order ";
      }

      // Plugin can override core definition for its type
      if ($plug = isPluginItemType($itemtype)) {
         $function = 'plugin_'.$plug['plugin'].'_addOrderBy';
         if (function_exists($function)) {
            $out = $function($itemtype, $ID, $order, $key);
            if (!empty($out)) {
               return $out;
            }
         }
      }

      switch($table.".".$field) {
         case "glpi_auth_tables.name" :
            $user_searchopt = Search::getOptions('User');
            return " ORDER BY `glpi_users`.`authtype` $order,
                              `glpi_authldaps".$addtable."_".
                                 Search::computeComplexJoinID($user_searchopt[30]['joinparams'])."`.`name` $order,
                              `glpi_authmails".$addtable."_".
                                 Search::computeComplexJoinID($user_searchopt[31]['joinparams'])."`.`name` $order ";

         case "glpi_users.name" :
            if ($itemtype!='User') {
               return " ORDER BY ".$table.$addtable.".`realname` $order,
                                 ".$table.$addtable.".`firstname` $order,
                                 ".$table.$addtable.".`name` $order";
            }
            return " ORDER BY `".$table."`.`name` $order";

         case "glpi_networkequipments.ip" :
         case "glpi_ipaddresses.name" :
            return " ORDER BY INET_ATON($table$addtable.$field) $order ";
      }

      //// Default cases

      // Link with plugin tables
      if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
         if (count($matches) == 2) {
            $plug     = $matches[1];
            $function = 'plugin_'.$plug.'_addOrderBy';
            if (function_exists($function)) {
               $out = $function($itemtype, $ID, $order, $key);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }

      // Preformat items
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "date_delay" :
               $interval = "MONTH";
               if (isset($searchopt[$ID]['delayunit'])) {
                  $interval = $searchopt[$ID]['delayunit'];
               }

               $add_minus = '';
               if (isset($searchopt[$ID]["datafields"][3])) {
                  $add_minus = "- `$table$addtable`.`".$searchopt[$ID]["datafields"][3]."`";
               }
               return " ORDER BY ADDDATE(`$table$addtable`.`".$searchopt[$ID]["datafields"][1]."`,
                                         INTERVAL (`$table$addtable`.`".$searchopt[$ID]["datafields"][2].
                                                   "` $add_minus)
                                         $interval) $order ";
         }
      }

      //return " ORDER BY $table.$field $order ";
      return " ORDER BY ITEM_$key $order ";

   }

  /**
   * Function set printer pager
   * 
   * @param object $item
   * @param array $input
   */
   function setPrintPager($item, $input) {
      $this->printAjaxPager($item, '', $this->current_search['start'], $this->number, $input);
   }
   
  /**
   * Function set export to pdf, csv ...
   * 
   * @param object $item
   * @param array $input
   */
   function setExport($item, $input) {
      global $CFG_GLPI;
      
      echo "<form method='POST' name='search_export$item->rand' target='_blank' action='".$this->getFormURL()."' 
               onsubmit=\"printecounters_reloadCsrf('".$CFG_GLPI['root_doc']."','search_export$item->rand');\">\n";
      $param = "";
      
      if(isset($input['searchopt'])) unset($input['searchopt']);
      
      echo "<input type='hidden' name='item' value='".Toolbox::prepareArrayForInput(serialize($item))."'>";
      
      foreach ($input as $key => $val) {
         if ($key != "_glpi_csrf_token") {
            if (is_array($val)) {
               foreach ($val as $k => $v) {
                  echo "<input type='hidden' name='".$key."[$k]' value='$v' >";
                  if (!empty($param)) {
                     $param .= "&";
                  }
                  $param .= $key."[".$k."]=".urlencode($v);
               }
            } else {
               echo "<input type='hidden' name='$key' value='$val' >";
               if (!empty($param)) {
                  $param .= "&";
               }
               $param .= "$key=".urlencode($val);
            }
         }
      }
      Dropdown::showOutputFormat();
      Html::closeForm();
   }

   /**
    * Print Ajax pager for list in tab panel
    *
    * @param $title displayed above
    * @param $start from witch item we start
    * @param $numrows total items
    *
    * @return nothing (print a pager)
    * */
   function printAjaxPager($item, $title, $start, $numrows, $input = array()) {
      global $CFG_GLPI;

      $_SESSION['glpilist_limit'] = $this->current_search['limit'];

      $list_limit = $_SESSION['glpilist_limit'];
      // Forward is the next step forward
      $forward = $start + $list_limit;

      // This is the end, my friend
      $end = $numrows - $list_limit;

      // Human readable count starts here
      $current_start = $start + 1;

      // And the human is viewing from start to end
      $current_end = $current_start + $list_limit - 1;
      if ($current_end > $numrows) {
         $current_end = $numrows;
      }
      // Empty case
      if ($current_end == 0) {
         $current_start = 0;
      }
      // Backward browsing
      if ($current_start - $list_limit <= 0) {
         $back = 0;
      } else {
         $back = $start - $list_limit;
      }

      // Print it
      echo "<table class='".($item->fixedDisplay ? "tab_cadre_pager" : "tab_cadrehov")."'>";
      if ($title) {
         echo "<tr><th colspan='6'>$title</th></tr>";
      }
      echo "<tr>\n";

      // Back and fast backward button
      if (!$start == 0) {
         echo "<th class='left'><a href='javascript:initSearch(\"".$CFG_GLPI["root_doc"]."\", \"search_form".$item->rand."\", \"history_showForm".$item->rand."\", \"0\", \"".$this->current_search['limit']."\", \"".$this->current_search['order']."\", \"".$this->current_search['sort']."\");'>
               <img src='".$CFG_GLPI["root_doc"]."/pics/first.png' alt=\"".__s('Start').
         "\" title=\"".__s('Start')."\"></a></th>";
         echo "<th class='left'><a href='javascript:initSearch(\"".$CFG_GLPI["root_doc"]."\", \"search_form".$item->rand."\", \"history_showForm".$item->rand."\", \"$back\", \"".$this->current_search['limit']."\", \"".$this->current_search['order']."\", \"".$this->current_search['sort']."\");'>
               <img src='".$CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".__s('Previous').
         "\" title=\"".__s('Previous')."\"></th>";
      }

      echo "<td width='50%' class='tab_bg_2 center'>";
      $this->printPagerForm($item);
      echo "</td>";

      // Doc export
      echo "<td class='tab_bg_2 center' width='30%'>";
      $this->setExport($item, $input);
      echo "</td>";

      // Print the "where am I?"
      echo "<td width='50%' class='tab_bg_2 b center'>";
      echo sprintf(__('From %1$d to %2$d on %3$d'), $current_start, $current_end, $numrows);
      echo "</td>\n";

      // Forward and fast forward button
      if ($forward < $numrows) {
         echo "<th class='right'><a href='javascript:initSearch(\"".$CFG_GLPI["root_doc"]."\", \"search_form".$item->rand."\", \"history_showForm".$item->rand."\", \"$forward\", \"".$this->current_search['limit']."\", \"".$this->current_search['order']."\", \"".$this->current_search['sort']."\");'>
               <img src='".$CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".__s('Next').
         "\" title=\"".__s('Next')."\"></a></th>";
         echo "<th class='right'><a href='javascript:initSearch(\"".$CFG_GLPI["root_doc"]."\", \"search_form".$item->rand."\", \"history_showForm".$item->rand."\", \"".$end."\", \"".$this->current_search['limit']."\", \"".$this->current_search['order']."\", \"".$this->current_search['sort']."\");'>
               <img src='".$CFG_GLPI["root_doc"]."/pics/last.png' alt=\"".__s('End').
         "\" title=\"".__s('End')."\"></th>";
      }

      // End pager
      echo "</tr></table>";
   }

   /**
    * Display the list_limit combo choice
    *
    * @param $action page would be posted when change the value (URL + param)
    * ajax Pager will be displayed if empty
    *
    * @return nothing (print a combo)
    * */
   function printPagerForm($item) {
      global $CFG_GLPI;

      echo "<form method='POST' action =''>\n";
      echo "<span>".__('Display (number of items)')."&nbsp;</span>";
      Dropdown::showListLimit("initSearch(\"".$CFG_GLPI["root_doc"]."\", \"search_form".$item->rand."\", \"history_showForm".$item->rand."\", \"0\", this.value, \"".$this->current_search['order']."\", \"".$this->current_search['sort']."\")");
      Html::closeForm();
   }
   
   
   /**
    * Generic Function to add left join to a request
    *
    * @param $itemtype                    item type
    * @param $ref_table                   reference table
    * @param $already_link_tables   array of tables already joined
    * @param $new_table                   new table to join
    * @param $linkfield                   linkfield for LeftJoin
    * @param $meta                        is it a meta item ? (default 0)
    * @param $meta_type                   meta type table (default 0)
    * @param $joinparams            array join parameters (condition / joinbefore...)
    *
    * @return Left join string
   **/
   static function addLeftJoin($itemtype, $ref_table, array &$already_link_tables, $new_table,
                                $linkfield, $meta=0, $meta_type=0, $joinparams=array()) {
      global $CFG_GLPI;

      // Rename table for meta left join
      $AS = "";
      $nt = $new_table;
      $cleannt    = $nt;

      // Multiple link possibilies case
//       if ($new_table=="glpi_users"
//           || $new_table=="glpi_groups"
//           || $new_table=="glpi_users_validation") {

//      if (!empty($linkfield) && ($linkfield != getForeignKeyFieldForTable($new_table))) {
//         $nt .= "_".$linkfield;
//         $AS  = " AS ".$nt;
//      }

      $complexjoin = search::computeComplexJoinID($joinparams);

      if (!empty($complexjoin)) {
//         $nt .= "_".$complexjoin;
         $AS  = " AS ".$nt;
      }

//       }

      $addmetanum = "";
      $rt         = $ref_table;
      $cleanrt    = $rt;
      if ($meta) {
         $addmetanum = "_".$meta_type;
         $AS         = " AS $nt$addmetanum";
         $nt         = $nt.$addmetanum;
      }


      // Auto link
      if (($ref_table == $new_table)
          && empty($complexjoin)) {
         return "";
      }

      // Do not take into account standard linkfield
      $tocheck = $nt.".".$linkfield;
      if ($linkfield == getForeignKeyFieldForTable($new_table)) {
         $tocheck = $nt;
      }
//       echo '->'.$tocheck.'<br>';

      if (in_array($tocheck,$already_link_tables)) {
         return "";
      }
      array_push($already_link_tables, $tocheck);

//        echo "DONE<br>";
      $specific_leftjoin = '';

      // Plugin can override core definition for its type
      if ($plug = isPluginItemType($itemtype)) {
         $function = 'plugin_'.$plug['plugin'].'_addLeftJoin';
         if (function_exists($function)) {
            $specific_leftjoin = $function($itemtype, $ref_table, $new_table, $linkfield,
                                           $already_link_tables);
         }
      }

      // Link with plugin tables : need to know left join structure
      if (empty($specific_leftjoin)
          && preg_match("/^glpi_plugin_([a-z0-9]+)/", $new_table, $matches)) {
         if (count($matches) == 2) {
            $function = 'plugin_'.$matches[1].'_addLeftJoin';
            if (function_exists($function)) {
               $specific_leftjoin = $function($itemtype, $ref_table, $new_table, $linkfield,
                                              $already_link_tables);
            }
         }
      }
      if (!empty($linkfield)) {
         $before = '';
//          Html::printCleanArray($joinparams);
         if (isset($joinparams['beforejoin']) && is_array($joinparams['beforejoin']) ) {

            if (isset($joinparams['beforejoin']['table'])) {
               $joinparams['beforejoin'] = array($joinparams['beforejoin']);
            }

            foreach ($joinparams['beforejoin'] as $tab) {
               if (isset($tab['table'])) {
                  $intertable = $tab['table'];
                  if (isset($tab['linkfield'])) {
                     $interlinkfield = $tab['linkfield'];
                  } else {
                     $interlinkfield = getForeignKeyFieldForTable($intertable);
                  }

                  $interjoinparams = array();
                  if (isset($tab['joinparams'])) {
                     $interjoinparams = $tab['joinparams'];
                  }
//                   echo "BEFORE ";
                  $before .= self::addLeftJoin($itemtype, $rt, $already_link_tables, $intertable,
                                               $interlinkfield, $meta, $meta_type, $interjoinparams);
//                   echo "END BEFORE ".'<br>';
               }

               // No direct link with the previous joins
               if (!isset($tab['joinparams']['nolink']) || !$tab['joinparams']['nolink']) {
                  $cleanrt     = $intertable;
                  $complexjoin = search::computeComplexJoinID($interjoinparams);
                  if (!empty($complexjoin)) {
//                     $intertable .= "_".$complexjoin;
                  }
                  $rt = $intertable.$addmetanum;
               }
            }
         }

         $addcondition = '';
         if (isset($joinparams['condition'])) {
            $from         = array("`REFTABLE`", "REFTABLE", "`NEWTABLE`", "NEWTABLE");
            $to           = array("`$rt`", "`$rt`", "`$nt`", "`$nt`");
            $addcondition = str_replace($from, $to, $joinparams['condition']);
            $addcondition = $addcondition." ";
         }

         if (!isset($joinparams['jointype'])) {
            $joinparams['jointype'] = 'standard';
         }

         if (empty($specific_leftjoin)) {
            switch ($new_table) {
               // No link
               case "glpi_auth_tables" :
                     $user_searchopt     = search::getOptions('User');

                     $specific_leftjoin  = self::addLeftJoin($itemtype, $rt, $already_link_tables,
                                                             "glpi_authldaps", 'auths_id', 0, 0,
                                                             $user_searchopt[30]['joinparams']);
                     $specific_leftjoin .= self::addLeftJoin($itemtype, $rt, $already_link_tables,
                                                             "glpi_authmails", 'auths_id', 0, 0,
                                                             $user_searchopt[31]['joinparams']);
                     break;
            }
         }

         if (empty($specific_leftjoin)) {
            switch ($joinparams['jointype']) {
               case 'child' :
                  $linkfield = getForeignKeyFieldForTable($cleanrt);
                  if (isset($joinparams['linkfield'])) {
                     $linkfield = $joinparams['linkfield'];
                  }

                  // Child join
                  $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                             ON (`$rt`.`id` = `$nt`.`$linkfield`
                                                 $addcondition)";
                  break;

               case 'item_item' :
                  // Item_Item join
                  $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON ((`$rt`.`id`
                                                   = `$nt`.`".getForeignKeyFieldForTable($cleanrt)."_1`
                                               OR `$rt`.`id`
                                                   = `$nt`.`".getForeignKeyFieldForTable($cleanrt)."_2`)
                                              $addcondition)";
                  break;

               case 'item_item_revert' :
                  // Item_Item join reverting previous item_item
                  $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON ((`$nt`.`id`
                                                   = `$rt`.`".getForeignKeyFieldForTable($cleannt)."_1`
                                               OR `$nt`.`id`
                                                   = `$rt`.`".getForeignKeyFieldForTable($cleannt)."_2`)
                                              $addcondition)";
                  break;

               case "itemtype_item" :
                  $used_itemtype = $itemtype;
                  if (isset($joinparams['specific_itemtype'])
                      && !empty($joinparams['specific_itemtype'])) {
                     $used_itemtype = $joinparams['specific_itemtype'];
                  }
                  // Itemtype join
                  $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON (`$rt`.`id` = `$nt`.`items_id`
                                              AND `$nt`.`itemtype` = '$used_itemtype'
                                              $addcondition) ";
                  break;

               default :
                  // Standard join
                  $specific_leftjoin = "LEFT JOIN `$new_table` $AS
                                          ON (`$rt`.`$linkfield` = `$nt`.`id`
                                              $addcondition)";
                  break;
            }
         }
//          echo $before.$specific_leftjoin.'<br>';
         return $before.$specific_leftjoin;
      }
 //     return '';
   }
   
   /**
    * Generic Function to add select to a request
    *
    * @param $itemtype     item type
    * @param $ID           ID of the item to add
    * @param $num          item num in the reque (default 0)
    * @param $meta         boolean is a meta
    * @param $meta_type    meta type table ID (default 0)
    *
    * @return select string
   **/
   static function addSelect($itemtype, $ID, $num, $meta=0, $meta_type=0) {
      global $CFG_GLPI;

      $searchopt   = &search::getOptions($itemtype);
      $table       = $searchopt[$ID]["table"];
      $field       = $searchopt[$ID]["field"];
      $addtable    = "";
      $NAME        = "ITEM";
      $complexjoin = '';

      if (isset($searchopt[$ID]['joinparams'])) {
         $complexjoin = search::computeComplexJoinID($searchopt[$ID]['joinparams']);
      }

      if (((($table != getTableForItemType($itemtype))
            && (!isset($CFG_GLPI["union_search_type"][$itemtype])
                || ($CFG_GLPI["union_search_type"][$itemtype] != $table)))
           || !empty($complexjoin))
          && ($searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table))) {
//         $addtable .= "_".$searchopt[$ID]["linkfield"];
      }

      if (!empty($complexjoin)) {
//         $addtable .= "_".$complexjoin;
      }

      if ($meta) {
         $NAME = "META";
         if (getTableForItemType($meta_type)!=$table) {
            $addtable .= "_".$meta_type;
         }
      }

      // Plugin can override core definition for its type
      if ($plug = isPluginItemType($itemtype)) {
         $function = 'plugin_'.$plug['plugin'].'_addSelect';
         if (function_exists($function)) {
            $out = $function($itemtype,$ID,$num);
            if (!empty($out)) {
               return $out;
            }
         }
      }

      $ADDITONALFIELDS = '';
      if (isset($searchopt[$ID]["additionalfields"])
          && count($searchopt[$ID]["additionalfields"])) {
         foreach ($searchopt[$ID]["additionalfields"] as $key) {
            $ADDITONALFIELDS .= "`$table$addtable`.`$key` AS ".$NAME."_".$num."_$key, ";
         }
      }

      switch ($table.".".$field) {
         case "glpi_tickets.due_date" :
            return " `$table$addtable`.`$field` AS ".$NAME."_$num,
                     `$table$addtable`.`status` AS ".$NAME."_".$num."_2,
                      $ADDITONALFIELDS";

         case "glpi_tickets.is_late" :
            return " IF(`$table$addtable`.`due_date` IS NOT NULL
                        AND (`$table$addtable`.`solvedate` > `$table$addtable`.`due_date`
                             OR (`$table$addtable`.`solvedate` IS NULL
                                 AND `$table$addtable`.`due_date` < NOW())),
                        1, 0) AS ".$NAME."_$num,
                     $ADDITONALFIELDS";

         case "glpi_contacts.completename" :
            // Contact for display in the enterprise item
            if ($_SESSION["glpinames_format"] == User::FIRSTNAME_BEFORE) {
               $name1 = 'firstname';
               $name2 = 'name';
            } else {
               $name1 = 'name';
               $name2 = 'firstname';
            }
            return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`$name1`, ' ',
                                                  `$table$addtable`.`$name2`, '$$',
                                                  `$table$addtable`.`id`)
                                  SEPARATOR '$$$$') AS ".$NAME."_$num,
                     $ADDITONALFIELDS";

         case "glpi_users.name" :
            if ($itemtype != 'User') {
               if ((isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
                  $addaltemail = "";
                  if ((($itemtype == 'Ticket') || ($itemtype == 'Problem'))
                      && isset($searchopt[$ID]['joinparams']['beforejoin']['table'])
                      && (($searchopt[$ID]['joinparams']['beforejoin']['table'] == 'glpi_tickets_users')
                          || ($searchopt[$ID]['joinparams']['beforejoin']['table']
                                == 'glpi_problems_users'))) { // For tickets_users

                     $ticket_user_table
                        = $searchopt[$ID]['joinparams']['beforejoin']['table'].
                          "_".search::computeComplexJoinID($searchopt[$ID]['joinparams']['beforejoin']['joinparams']);
                     $addaltemail
                        = "GROUP_CONCAT(DISTINCT CONCAT(`$ticket_user_table`.`users_id`, ' ',
                                                        `$ticket_user_table`.`alternative_email`)
                                                        SEPARATOR '$$$$') AS ".$NAME."_".$num."_2, ";
                  }
                  return " GROUP_CONCAT(DISTINCT `$table$addtable`.`id` SEPARATOR '$$$$')
                                       AS ".$NAME."_".$num.",
                           $addaltemail
                           $ADDITONALFIELDS";

               }
               return " `$table$addtable`.`$field` AS ".$NAME."_$num,
                        `$table$addtable`.`realname` AS ".$NAME."_".$num."_2,
                        `$table$addtable`.`id`  AS ".$NAME."_".$num."_3,
                        `$table$addtable`.`firstname` AS ".$NAME."_".$num."_4,
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_groups.name" :
            if (($itemtype != 'Group') && ($itemtype != 'User')) {
               if ($meta
                   || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
                  return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`$field`,'$$',
                                                        `$table$addtable`.`id`) SEPARATOR '$$$$')
                                       AS ".$NAME."_$num, ";
               }
               return " `$table$addtable`.`$field` AS ".$NAME."_$num,
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_softwarelicenses.number" :
            return " FLOOR(SUM(`$table$addtable`.`$field`)
                           * COUNT(DISTINCT `$table$addtable`.`id`)
                           / COUNT(`$table$addtable`.`id`)) AS ".$NAME."_".$num.",
                     MIN(`$table$addtable`.`$field`) AS ".$NAME."_".$num."_2,
                      $ADDITONALFIELDS";

         case "glpi_documents_items.count" :
            return " COUNT(DISTINCT `glpi_documents_items`.`id`) AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_contracts_items.count" :
            return " COUNT(DISTINCT `glpi_contracts_items`.`id`) AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_contractcosts.totalcost" :
            return " SUM(`glpi_contractcosts$addtable`.`cost`)
                     / COUNT(`glpi_contractcosts$addtable`.`id`)
                     * COUNT(DISTINCT `glpi_contractcosts$addtable`.`id`)
                     AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_computers_softwareversions.count" :
            return " COUNT(DISTINCT `glpi_computers_softwareversions$addtable`.`id`)
                          AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_items_deviceharddrives.capacity" :
            if ($itemtype != 'DeviceHardDrive') {
               return " SUM(`glpi_items_deviceharddrives`.`capacity`)
                        / COUNT(`glpi_items_deviceharddrives`.`id`)
                        * COUNT(DISTINCT `glpi_items_deviceharddrives`.`id`) AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_items_devicememories.size" :
            if ($itemtype != 'DeviceMemory') {
               return " SUM(`glpi_items_devicememories`.`size`)
                        / COUNT(`glpi_items_devicememories`.`id`)
                        * COUNT(DISTINCT `glpi_items_devicememories`.`id`) AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_items_deviceprocessors.frequency" :
            if ($itemtype != 'DeviceProcessor') {
               return " SUM(`glpi_items_deviceprocessors`.`frequency`)
                        / COUNT(`glpi_items_deviceprocessors`.`id`) AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_problems.count" :
         case "glpi_problemtasks.count" :
         case "glpi_tickets.count" :
         case "glpi_ticketfollowups.count" :
         case "glpi_tickettasks.count" :
         case "glpi_tickets_tickets.count" :
         case "glpi_items_problems.count" :
         case "glpi_problems_tickets.count" :
            return " COUNT(DISTINCT `$table$addtable`.`id`) AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_ticketcosts.cost_time" :
         case "glpi_ticketcosts.cost_fixed" :
         case "glpi_ticketcosts.cost_material" :
            return " SUM(`glpi_ticketcosts$addtable`.`$field`)
                     / COUNT(`glpi_ticketcosts$addtable`.`id`)
                     * COUNT(DISTINCT `glpi_ticketcosts$addtable`.`id`)
                     AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_ticketcosts.totalcost" :
            return " SUM(`glpi_ticketcosts$addtable`.`actiontime`
                         * `glpi_ticketcosts$addtable`.`cost_time`/".HOUR_TIMESTAMP."
                         + `glpi_ticketcosts$addtable`.`cost_fixed`
                         + `glpi_ticketcosts$addtable`.`cost_material`)
                     / COUNT(`glpi_ticketcosts$addtable`.`id`)
                     * COUNT(DISTINCT `glpi_ticketcosts$addtable`.`id`)
                     AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_tickets_tickets.tickets_id_1" :
            return " GROUP_CONCAT(`$table$addtable`.`tickets_id_1` SEPARATOR '$$$$')
                                 AS ".$NAME."_$num,
                     GROUP_CONCAT(`$table$addtable`.`tickets_id_2` SEPARATOR '$$$$')
                                 AS ".$NAME."_".$num."_2,
                     $ADDITONALFIELDS";

         case "glpi_networkports.mac" :
            $port = " GROUP_CONCAT(`$table$addtable`.`$field` SEPARATOR '$$$$')
                                  AS ".$NAME."_$num, ";
            if ($itemtype == 'Computer') {
               $port .= " GROUP_CONCAT(`glpi_items_devicenetworkcards`.`mac` SEPARATOR '$$$$')
                                      AS ".$NAME."_".$num."_2, ";
            }
            return $port.$ADDITONALFIELDS;

         case "glpi_profiles.name" :
            if (($itemtype == 'User')
                && ($ID == 20)) {
               return " GROUP_CONCAT(`$table$addtable`.`$field` SEPARATOR '$$$$') AS ".$NAME."_$num,
                        GROUP_CONCAT(`glpi_profiles_users`.`entities_id` SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num."_2,
                        GROUP_CONCAT(`glpi_profiles_users`.`is_recursive` SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num."_3,
                        GROUP_CONCAT(`glpi_profiles_users`.`is_dynamic` SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num."_4,
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_entities.completename" :
            if (($itemtype == 'User')
                && ($ID == 80)) {
               return " GROUP_CONCAT(`$table$addtable`.`completename` SEPARATOR '$$$$')
                                    AS ".$NAME."_$num,
                        GROUP_CONCAT(`glpi_profiles_users`.`profiles_id` SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num."_2,
                        GROUP_CONCAT(`glpi_profiles_users`.`is_recursive` SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num."_3,
                        GROUP_CONCAT(`glpi_profiles_users`.`is_dynamic` SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num."_4,
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_auth_tables.name":
            $user_searchopt = search::getOptions('User');
            return " `glpi_users`.`authtype` AS ".$NAME."_".$num.",
                     `glpi_users`.`auths_id` AS ".$NAME."_".$num."_2,
                     `glpi_authldaps".$addtable."_".
                           search::computeComplexJoinID($user_searchopt[30]['joinparams'])."`.`$field`
                              AS ".$NAME."_".$num."_3,
                     `glpi_authmails".$addtable."_".
                           search::computeComplexJoinID($user_searchopt[31]['joinparams'])."`.`$field`
                              AS ".$NAME."_".$num."_4,
                     $ADDITONALFIELDS";

         case "glpi_softwarelicenses.name" :
         case "glpi_softwareversions.name" :
            if ($meta) {
               return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                     `$table$addtable`.`$field`, '$$',
                                                     `$table$addtable`.`id`) SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_softwarelicenses.serial" :
         case "glpi_softwarelicenses.otherserial" :
         case "glpi_softwarelicenses.comment" :
         case "glpi_softwareversions.comment" :
            if ($meta) {
               return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                     `$table$addtable`.`$field`,'$$',
                                                     `$table$addtable`.`id`) SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`name`, ' - ',
                                                  `$table$addtable`.`$field`, '$$',
                                                  `$table$addtable`.`id`) SEPARATOR '$$$$')
                                 AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_states.name" :
            if ($meta && ($meta_type == 'Software')) {
               return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                     `glpi_softwareversions$addtable`.`name`, ' - ',
                                                     `$table$addtable`.`$field`, '$$',
                                                     `$table$addtable`.`id`) SEPARATOR '$$$$')
                                     AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            } else if ($itemtype == 'Software') {
               return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwareversions`.`name`, ' - ',
                                                     `$table$addtable`.`$field`,'$$',
                                                     `$table$addtable`.`id`) SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            break;

         case 'glpi_crontasks.description' :
            return " `glpi_crontasks`.`name` AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case 'glpi_notifications.event' :
            return " `glpi_notifications`.`itemtype` AS `itemtype`,
                     `glpi_notifications`.`event` AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case 'glpi_tickets.name' :
            if (isset($searchopt[$ID]['forcegroupby']) && $searchopt[$ID]['forcegroupby']) {
               return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`$field`,'$$',
                                                     `$table$addtable`.`id`) SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            return " `$table$addtable`.`$field` AS ".$NAME."_$num,
                     `$table$addtable`.`id` AS ".$NAME."_".$num."_2,
                     `$table$addtable`.`content` AS ".$NAME."_".$num."_3,
                     `$table$addtable`.`status` AS ".$NAME."_".$num."_4,
                     $ADDITONALFIELDS";

         case 'glpi_tickets.items_id':
            return " `$table$addtable`.`$field` AS ".$NAME."_$num,
                     `$table$addtable`.`itemtype` AS ".$NAME."_".$num."_2,
                     $ADDITONALFIELDS";
      }

      //// Default cases
      // Link with plugin tables
      if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
         if (count($matches) == 2) {
            $plug     = $matches[1];
            $function = 'plugin_'.$plug.'_addSelect';
            if (function_exists($function)) {
               $out = $function($itemtype, $ID, $num);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }

      $tocompute   = "`$table$addtable`.`$field`";
      $tocomputeid = "`$table$addtable`.`id`";

      if (isset($searchopt[$ID]["computation"])) {
         $tocompute = $searchopt[$ID]["computation"];
         $tocompute = str_replace("TABLE", "`$table$addtable`", $tocompute);
      }

      // Preformat items
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "date_delay" :
               $interval = "MONTH";
               if (isset($searchopt[$ID]['delayunit'])) {
                  $interval = $searchopt[$ID]['delayunit'];
               }

               $add_minus = '';
               if (isset($searchopt[$ID]["datafields"][3])) {
                  $add_minus = "-`$table$addtable`.`".$searchopt[$ID]["datafields"][3]."`";
               }
               if ($meta
                   || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
                  return " GROUP_CONCAT(DISTINCT ADDDATE(`$table$addtable`.`".
                                                            $searchopt[$ID]["datafields"][1]."`,
                                                         INTERVAL (`$table$addtable`.`".
                                                                     $searchopt[$ID]["datafields"][2].
                                                                     "` $add_minus) $interval)
                                         SEPARATOR '$$$$') AS ".$NAME."_$num,
                           $ADDITONALFIELDS";
               }
               return "ADDDATE(`$table$addtable`.`".$searchopt[$ID]["datafields"][1]."`,
                               INTERVAL (`$table$addtable`.`".$searchopt[$ID]["datafields"][2].
                                          "` $add_minus) $interval) AS ".$NAME."_$num,
                       $ADDITONALFIELDS";

            case "itemlink" :
               if ($meta
                  || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
                  return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`$field`, '$$' ,
                                                        `$table$addtable`.`id`) SEPARATOR '$$$$')
                                       AS ".$NAME."_$num,
                           $ADDITONALFIELDS";
               }
               return " $tocompute AS ".$NAME."_$num,
                        `$table$addtable`.`id` AS ".$NAME."_".$num."_2,
                        $ADDITONALFIELDS";
         }
      }
      // Default case
      if ($meta
         || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
         return " GROUP_CONCAT(DISTINCT CONCAT($tocompute,'$$',$tocomputeid) SEPARATOR '$$$$')
                              AS ".$NAME."_$num,
                  $ADDITONALFIELDS";
      }
      return "$tocompute AS ".$NAME."_$num,
              $ADDITONALFIELDS";
   }
   
   /**
    * Function compare search data
    * 
    * @param type $itemtype
    * @param type $search_parameters
    * @param type $data_array : array([search item num], [value])
    * @return type
    */
   function compareData($itemtype, $search_parameters, $data_array){
      
      $OK = true;

//      Toolbox::logDebug($search_parameters);

      if (!empty($search_parameters)) {
         $search     = array();
         $searchlink = array();
         $options    = Search::getCleanedOptions($itemtype);

         foreach ($search_parameters as $key => $value) {      
            foreach ($data_array as $num => $data) {
               if ($num == $value['search_item']) {
                  foreach ($options as $options_num => $val) {
                     if ($options_num == $num) {
                        $type = $val['datatype'];
                        break;
                     }
                  }
                  
                  // Specific search
                  switch ($type) {
                     case 'datetime':
                        $force_day = false;
                        if (strstr($value['contains'], 'BEGIN') || strstr($value['contains'], 'LAST')) {
                           $force_day = true;
                        }
                        $value['contains'] = Html::computeGenericDateTimeSearch($value['contains'], $force_day);

                        if ($value['searchtype'] == 'contains') {
                           $data = Html::convDateTime(date('Y-m-d H:i:s', $data));
                        } else {
                           $data = strtotime(date('Y-m-d H:i', $data));
                           $value['contains'] = strtotime(date('Y-m-d H:i', strtotime($value['contains'])));
                        }
                        break;
                  }

                  // Compare date
                  $search[$key] = 1;

                  if ($value['searchtype'] == 'equals' && $value['contains'] != $data) {
                     $search[$key] = 0;

                  } elseif ($value['searchtype'] == 'notequals' && $value['contains'] == $data) {
                     $search[$key] = 0;

                  } elseif ($value['searchtype'] == 'lessthan' && $data >= $value['contains']) {
                     $search[$key] = 0;

                  } elseif ($value['searchtype'] == 'morethan' && $data <= $value['contains']) {
                     $search[$key] = 0;

                  } elseif ($value['searchtype'] == 'contains' && !preg_match('/'.$value['contains'].'/', Html::convDateTime(date('Y-m-d H:i:s', $data)))) {
                     $search[$key] = 0;
                  }

                  if ($value['NOT']) {
                     $search[$key] = !$search[$key];
                  }

                  $searchlink[$key] = trim($value['LINK']);
               }
            }
         }

//         Toolbox::logDebug('########################## Search result ############################');
//         Toolbox::logDebug($search);
//         Toolbox::logDebug($searchlink);

         // Compare each search parameter line
         foreach ($search as $key => $planned) {
            switch ($searchlink[$key]) {
               case 'AND':
                  if (isset($search[$key - 1])) {
                     $search[$key] = ($search[$key] && $search[$key - 1]);
                     $OK = $search[$key];
                  } else {
                     $OK = $search[$key];
                  }
                  break;

               case 'OR':
                  if (isset($search[$key - 1])) {
                     $search[$key] = ($search[$key] || $search[$key - 1]);
                     $OK = $search[$key];
                  } else {
                     $OK = $search[$key];
                  }
                  break;

               default :
                  $OK = $search[$key];
                  break;
            }
         }
      }
//      Toolbox::logDebug('#################### OK ? ######################');
//      Toolbox::logDebug($OK);
      return $OK;
   }

}