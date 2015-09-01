# bootstrap_orderable_frontend

## Maintainers

 * Andre Lohmann (Nickname: andrelohmann)
  <lohmann dot andre at googlemail dot com>

## Requirements

Silverstripe 3.2.x

## Overview

This Module provides a nice paradigm to build orderable collections on the frontend
The orderable list will be paginated and ajaxified (
 
## Usage

create an OrderablePaginatedList in your controller action and add an ajax statement
```php
class MyController extends Controller {
	
	...

	public function orderableobjects(){
		
        $List = MyOrderableObject::get()->sort('Sort');
        $MyOrderableObjects = new OrderablePaginatedList($List, $this->request);
		$MyOrderableObjects->setPageLength(10)->setSortField('Sort');
        // if the sortable list is a many many relation
        // $MyOrderableObjects->setOwner($OwnerObject)->setManyMany('NAME_OF_MANY_MANY_RELATION');
		
		if($this->request->isAjax()) {
			return $this->customise(array(
                "Objects" => $MyOrderableObjects->process(), // process the ordering after OrderablePaginatedList has all information it needs (pageLength, SortField, Owner, ManyManyRelation)
				"URL" => $this->request->getURL(true) // add this for BackURL parameter
            ))->renderWith('OrderableObjectsList');
		}
		
		return $this->customise(new ArrayData(array(
			"Title" => "My Orderable Objects",
			"Objects" => $MyOrderableObjects,
			"URL" => $this->request->getURL(true) // add this for BackURL parameter
		)))->renderWith(
			array('Page_orderableobjects', $this->stat('template_main'), $this->stat('template'))
        );
	}
```

use templates/Layout/Page_orderableobjects.ss and templates/Includes/OrderableObjectsList.ss as base templates to build your own orderable paginated lists

### Notice
This repository uses the git flow paradigm.
After each release cycle, do not forget to push tags, master and develop to the remote origin
```
git push --tags
git push origin develop
git push origin master
```