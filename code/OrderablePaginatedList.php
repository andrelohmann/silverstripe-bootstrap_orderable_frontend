<?php

/**
 * Allows to Order Collections on the Frontend
 */
class OrderablePaginatedList extends PaginatedList {

	/**
	 * The database field which specifies the sort, defaults to "Sort".
	 *
	 * @see setSortField()
	 * @var string
	 */
	protected $sortField = 'Sort';
	
	protected $owner = null;
	
	protected $many_many = null;
	
	public function __construct(\SS_List $list, $request = array()) {
		parent::__construct($list, $request);
		
		if(!$this->request->isAjax()){
			$this->initRequirements();
		}
	}

	/**
	 * process the reordering
	 * 
	 * this needs to be done from the outside, to take care of all set Variables (pagelength, Sortfield, owner, many_many_relation)
	 *
	 * @return OrderablePaginatedList $this
	 */
	public function process() {
		
		if($this->request->isAjax() && $this->request->isPost() && $this->request->postVar('doOrder')){
			$this->processOrdering();
		}
		return $this;
	}
	
	protected function initRequirements(){
		
		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');

        Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
        Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-ui/jquery-ui.js');
        Requirements::javascript(THIRDPARTY_DIR . '/json-js/json2.js');
        Requirements::javascript(FRAMEWORK_DIR . '/javascript/i18n.js');
        Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');
        Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
        Requirements::javascript(FRAMEWORK_DIR . '/javascript/GridField.js');

        Requirements::css('bootstrap_orderable_frontend/css/OrderablePaginatedList.css');
		
        Requirements::javascript('bootstrap_orderable_frontend/javascript/OrderablePaginatedList.js');
	}

	/**
	 * @return string
	 */
	public function getSortField() {
		return $this->sortField;
	}

	/**
	 * Sets the field used to specify the sort.
	 *
	 * @param string $sortField
	 * @return OrderablePaginatedList $this
	 */
	public function setSortField($field) {
		$this->sortField = $field;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getOwner() {
		return $this->owner;
	}

	/**
	 * Sets the the owner object of the many_many relation
	 *
	 * @param string $owner
	 * @return OrderablePaginatedList $this
	 */
	public function setOwner($owner) {
		$this->owner = $owner;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getManyMany() {
		return $this->many_many;
	}

	/**
	 * Sets the ManyMnay Rleation Name of owner
	 *
	 * @param string $many_many
	 * @return OrderablePaginatedList $this
	 */
	public function setManyMany($many_many) {
		$this->many_many = $many_many;
		return $this;
	}

	/**
	 * Gets the table which contains the sort field.
	 *
	 * @param DataList $list
	 * @return string
	 */
	protected function getSortTable(DataList $list) {
		$field = $this->getSortField();

		if($list instanceof ManyManyList) {
			$extra = $list->getExtraFields();
			$table = $list->getJoinTable();

			if($extra && array_key_exists($field, $extra)) {
				return $table;
			}
		}

		$classes = ClassInfo::dataClassesFor($list->dataClass());

		foreach($classes as $class) {
			if(singleton($class)->hasOwnTableDatabaseField($field)) {
				return $class;
			}
		}

		throw new Exception("Couldn't find the sort field '$field'");
	}

	protected function populateSortValues(DataList $list, $many_many) {
		$list   = clone $list;
		$field  = $this->getSortField();
		$table  = $this->getSortTable($list);
		$clause = sprintf('"%s"."%s" = 0', $table, $this->getSortField());

		foreach($list->where($clause)->column('ID') as $id) {
			$max = DB::query(sprintf('SELECT MAX("%s") + 1 FROM "%s"', $field, $table));
			$max = $max->value();

			DB::query(sprintf(
				'UPDATE "%s" SET "%s" = %d WHERE %s',
				$table,
				$field,
				$max,
				$this->getSortTableClauseForIds($list, $id).$many_many
			));
		}
	}

	protected function getSortTableClauseForIds(DataList $list, $ids) {
		if(is_array($ids)) {
			$value = 'IN (' . implode(', ', array_map('intval', $ids)) . ')';
		} else {
			$value = '= ' . (int) $ids;
		}

		if($list instanceof ManyManyList) {
			$extra = $list->getExtraFields();
			$key   = $list->getLocalKey();

			if(array_key_exists($this->getSortField(), $extra)) {
				return sprintf('"%s" %s', $key, $value);
			}
		}

		return "\"ID\" $value";
	}

	protected function reorderItems($list, array $values, array $order, $many_many) {
		// Get a list of sort values that can be used.
		$pool = array_values($values);
		sort($pool);

		// Loop through each item, and update the sort values which do not
		// match to order the objects.
		foreach(array_values($order) as $pos => $id) {
			if($values[$id] != $pool[$pos]) {
				DB::query(sprintf(
					'UPDATE "%s" SET "%s" = %d WHERE %s',
					$this->getSortTable($list),
					$this->getSortField(),
					$pool[$pos],
					$this->getSortTableClauseForIds($list, $id).$many_many
				));
			}
		}
	}
	
	protected function processOrdering(){
		// change order
		if($this->request->postVar('order')){
			$this->reorderObjects();
		}else if($this->request->postVar('move')){
			$this->moveToPage();
		}else{
			throw new Exception("Couldn't find valid ordering parameters");
		}
	}
	
	protected function reorderObjects(){
		if(!singleton($this->list->dataClass())->canEdit()) {
			throw new Exception("canEdit() not allowed on ".$this->list->dataClass());
		}

		$ids   = $this->request->postVar('order');
		$list  = $this->list;
		$field = $this->getSortField();
                
                if($list instanceof ManyManyList){
					if(!$this->getOwner() || !$this->getManyMany()) throw new Exception("Owner and ManyMany Relation missing on OrderablePaginatedList");
                    $owner = $this->getOwner();
                    list($parentClass, $componentClass, $parentField, $componentField, $table) = $owner->many_many($this->getManyMany());
                    $many_many = ' AND "' . $parentField . '" = ' . $owner->ID;
                }else{
                    $many_many = '';
                }

		if(!is_array($ids)) {
			throw new Exception("No IDs given on OrderablePaginatedList");
		}

		$items = $list->byIDs($ids)->sort($field);

		// Ensure that each provided ID corresponded to an actual object.
		if(count($items) != count($ids)) {
			throw new Exception("IDs didn't correspond to actual objects on OrderablePaginatedList");
		}

		// Populate each object we are sorting with a sort value.
		$this->populateSortValues($items, $many_many);

		// Generate the current sort values.
		$current = $items->map('ID', $field)->toArray();

		// Perform the actual re-ordering.
		$this->reorderItems($list, $current, $ids, $many_many);
		
	}
	
	protected function moveToPage(){

		$move  = $this->request->postVar('move');
		$field = $this->getSortField();

		$list  = $this->list;
                
                if($list instanceof ManyManyList){
                    if(!$this->getOwner() || !$this->getManyMany()) throw new Exception("Owner and ManyMany Relation missing on OrderablePaginatedList");
                    $owner = $this->getOwner();
                    list($parentClass, $componentClass, $parentField, $componentField, $table) = $owner->many_many($this->getManyMany());
                    $many_many = ' AND "' . $parentField . '" = ' . $owner->ID;
                }else{
                    $many_many = '';
                }


		$id = isset($move['id']) ? (int) $move['id'] : null;
		$to = isset($move['page']) ? $move['page'] : null;

		$page = $this->CurrentPage();
		$per  = $this->getPageLength();
		
		$items = $list->sort($field)->limit($per, (($page - 1) * $per));
		$existing = $items->map('ID', $field)->toArray();
		$values   = $existing;
		$order    = array();
		
		if(!isset($values[$id])) {
			throw new Exception("Invalid item id ".$id." on OrderablePaginatedList");
		}

		$this->populateSortValues($list, $many_many);
		
		/*var_dump(array(
			'to' => $to,
			'id' => $id,
			'page' => $page,
			'per' => $per
		)); die();*/

		if($to == 'prev') {
			$swap = $list->sort($this->getSortField())->limit(1, ($page - 1) * $per - 1)->first();
			$values[$swap->ID] = $swap->$field;

			$order[] = $id;
			$order[] = $swap->ID;

			foreach($existing as $_id => $sort) {
				if($id != $_id) $order[] = $_id;
			}
		} elseif($to == 'next') {
			$swap = $list->sort($this->getSortField())->limit(1, $page * $per)->first();
			$values[$swap->ID] = $swap->$field;

			foreach($existing as $_id => $sort) {
				if($id != $_id) $order[] = $_id;
			}

			$order[] = $swap->ID;
			$order[] = $id;
		} else {
			throw new Exception("Invalid page target on OrderablePaginatedList");
		}

		$this->reorderItems($list, $values, $order, $many_many);
	}
	
}