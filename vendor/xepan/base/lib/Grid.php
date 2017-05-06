<?php

namespace xepan\base;

class Grid extends \Grid{
	
    public $row_edit=true;
	public $row_delete=true;
    public $defaultTemplate = null;
    public $paginator_class='xepan\base\Paginator';

    public $sort_icons = array(
        ' fa fa-sort',
        ' fa fa-sort-asc',
        ' fa  fa-sort-desc'
    );

    function defaultTemplate(){
        if($this->defaultTemplate) return $this->defaultTemplate;
        return parent::defaultTemplate();
    }

    function removeSearchIcon(){
        $this->template->tryDel('quick_search_icon');
        return $this;
    }
	
	function precacheTemplate(){
		if($this->template->template_file != 'grid'){
            foreach ($this->columns as $name => $column) {
                if (isset($column['sortable'])) {
                    $s = $column['sortable'];
                    $temp_template= $this->add('GiTemplate')
                        ->loadTemplateFromString('<span class="{$sorticon}">');
                    $temp_template->trySet('order', $s[0])
                        ->trySet('sorticon', $this->sort_icons[$s[0]]);
                    $this->template
                        ->trySet($name.'_sortid', $sel = $this->name.'_sort_'.$name)
                        ->trySetHTML($name.'_sort', $temp_template->render());

                    $this->js('click', $this->js()->reload(array($this->name.'_sort'=>$s[1])))
                        ->_selector('#'.$sel);
                }
            }  
            return;
        }
		return parent::precacheTemplate();
	}

	function formatRow(){

	    parent::formatRow();

	    if($this->owner instanceof \CRUD){
            if(!$this->current_row_html['edit']){
                if($this->row_edit)
                    $this->current_row_html['edit']= '<a class="table-link pb_edit" href="#" data-id="'.$this->model->id.'"><span class="fa-stack"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-pencil fa-stack-1x fa-inverse"></i></span></a>';
                else
                    $this->current_row_html['edit']= '<span class="fa-stack table-link"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-pencil fa-stack-1x fa-inverse"></i></span>';
            }

            if(!$this->current_row_html['delete']){
    			if($this->row_delete)
    			    $this->current_row_html['delete']= '<a class="table-link danger do-delete" href="#" data-id="'.$this->model->id.'"><span class="fa-stack"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-trash-o fa-stack-1x fa-inverse"></i></span></a>';
    			else
    			    $this->current_row_html['delete']= '<span class="table-link fa-stack"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-trash-o fa-stack-1x fa-inverse"></i></span>';
            }
	    }
	}

	function applyTDParams($field, &$row_template = null)
    {
        // data row template by default
        if (!$row_template) {
            $row_template = &$this->row_t;
        }

        // setting cell parameters (tdparam)
        $tdparam = @$this->tdparam[$this->getCurrentIndex()][$field];
        $tdparam_str = '';
        if (is_array($tdparam)) {
            if (is_array($tdparam['style'])) {
                $tdparam_str .= 'style="';
                foreach ($tdparam['style'] as $key=>$value) {
                    $tdparam_str .= $key . ':' . $value . ';';
                }
                $tdparam_str .= '" ';
                unset($tdparam['style']);
            }

            //walking and combining string
            foreach ($tdparam as $id=>$value) {
                $tdparam_str .= $id . '="' . $value . '" ';
            }

            // set TD param to appropriate row template
            $row_template->trySet("tdparam_$field", trim($tdparam_str));
        }
    }

    /**
     * Initialize column with delete buttons
     *
     * @param string $field
     *
     * @return void
     */
    function init_delete($field)
    {
        // set special CSS class for delete buttons to add some styling
        $this->columns[$field]['button_class'] = 'atk-effect-danger atk-delete-button';
        $this->columns[$field]['icon'] = 'trash';

        // if this was clicked, then delete record
        if ($id = @$_GET[$this->name.'_'.$field]) {

            // delete record
            $this->_performDelete($id);

            if($this->app->db->inTransaction()) $this->app->db->commit();
            // show message
            $this->js()->univ()
                ->successMessage('Deleted Successfully')
                ->reload()
                ->execute();
        }

        // move button column at the end (to the right)
        $self = $this;
        $this->app->addHook('post-init', function() use($self, $field) {
            if ($self->hasColumn($field)) {
                $self->addOrder()->move($field, 'last')->now();
            }
        });

        // ask for confirmation
        $this->init_confirm($field);
    }

    function format_gmdate($f){
        $this->current_row[$f] = gmdate("H:i:s", $this->current_row[$f]);
    }

    function render(){
        $this->js(true)->_load('footable')->_css('libs/footable.core')->find('table')->footable();
        parent::render();
    }

}
