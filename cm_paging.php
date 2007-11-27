<?
class cm_paging {
    public $records_per_page = 0;
    public $page_no = 1;
    public $no_of_records;
    public $paging_container_style;
    public $max_jump_links = 11; #it's best if this is an odd number. You are liable to get mildly unexpected results with an even number
    public $per_page_options =  array(10, 30, 50, 100, 200);
    public $default_per_page_options = 1; #array index

    function __construct($no_of_records) {
        $this->no_of_records = $no_of_records;

        #page no
            if (isset($_GET['page_no']) && is_numeric($_GET['page_no'])) {
                $this->page_no = $_GET['page_no'];
            } 
            else {
                $this->page_no = 1;
            }
        #$this->sanitize_page_numbers(); don't call this method from here - it may force the page number back to one if you are using a custom records_per_page
    }
    function limit_sql() {
        $this->sanitize_page_numbers();
        if (0 == $this->no_of_records) { return '0, 0'; } #no records
        
        $sql = (($this->page_no-1) * $this->records_per_page).', '.$this->records_per_page;
        return $sql;

    }

    function sanitize_page_numbers() {
        if ($this->records_per_page == 0 ) { $this->records_per_page = $this->per_page_options[$this->default_per_page_options]; }
        $this->highest_page_no = ceil($this->no_of_records / $this->records_per_page);
        if ($this->page_no > $this->highest_page_no) {
            $this->page_no = 1;
        }

        if ($this->no_of_records == 0) { $this->page_no = 0; }
    }

    function page_description($record_descriptor) {
        ?><h4 class="paging_page_description">Viewing <? if ($this->page_no > 0 && $this->highest_page_no > 1) { 
            $start_record = (($this->page_no-1) * $this->records_per_page)+1;
            $end_record = ($this->page_no) * $this->records_per_page;
            if ($end_record > $this->no_of_records) { $end_record = $this->no_of_records; }
            ?><?=$start_record;?> to <?=$end_record;?> of <?
        }
        ?><?=$this->no_of_records;?> <?
        if ($this->no_of_records != 1) {
            echo humanize(pluralize($record_descriptor));
        }
        else {
            echo humanize($record_descriptor);
        }
        ?></h4><?
    }
    function paging_anchors($return_as_string = false) {
        $this->callbacks = $callbacks;
        $this->sanitize_page_numbers();

        if ($return_as_string) { ob_start(); }

        ?><div class="paging_container" <? if ($this->paging_container_style) { ?>style="<?=$this->paging_container_style;?>"<? } ?>><?
        

            #moving between pages
                ?><div class="paging_page_numbers"><table style="width: 100%;"><tr><?
                ?><td><?
                if ($this->page_no > 1) {
                    $this->link_to_page($this->page_no-1, null, '&lt;&lt; page '.($this->page_no-1), 'previous');
                }
                ?></td><td><?
                ?><h4 class="paging_title">Page <?=$this->page_no;?> of <?=$this->highest_page_no;?></h4><?
                #records per page link, disabled for now
                if ($this->no_of_records > 0 && 1==2) {
                    ?><span class="paging_records_per_page"><select id="records_per_page" name="records_per_page" onchange="<?
                    ?>window.location='<?=page_parameters('/^page_no$/,/^records_per_page$/');?>&records_per_page='+this[this.selectedIndex].value;<?
                    ?>"><?
                    foreach ($this->per_page_options as $per_page_option) {
                        ?><option value="<?=$per_page_option?>"<?;
                        if($this->records_per_page == $per_page_option) { ?> "selected="selected"<?  }
                        ?>><?=$per_page_option;?><?
                        if($this->records_per_page == $per_page_option) { ?> records per page<?  }
                        ?></option><?
                    }      
                    ?></select></span><?
                }

                ?></td><td><?
                
                if (($this->records_per_page * (int)$this->page_no) < $this->no_of_records) {
                    $this->link_to_page($this->page_no+1, null, 'page '.($this->page_no+1).' &gt;&gt;','next');
                }
                ?></td></tr></table><?

                ?></div><?
                if ($this->highest_page_no > 1) {
                    $jump_pages = array();
                    $half_a_jump_block = (int)(($this->max_jump_links - 1) / 2);
                
                #link to all pages if less than max_jump_links pages
                    if ($this->highest_page_no < $this->max_jump_links) {
                        $jump_pages = range(1, $this->highest_page_no);
                        #for ($i = 1; $i <= $this->highest_page_no; $i++) { $jump_pages[] = $i; }
                    }
                    else
                    {
                        #get the left and right distances
                            $left_distance = $this->page_no-1;
                            $right_distance = $this->highest_page_no - $this->page_no;

                        #calculate the bonuses
                            $left_bonus = $right_bonus = 0;
                            if ( $left_distance < $half_a_jump_block ) { $right_bonus = $half_a_jump_block - $left_distance; }
                            if ( $right_distance < $half_a_jump_block ) { $left_bonus = $half_a_jump_block - $right_distance; }

                        #if the left distance is < $half_a_jump_block then the right distance CANT be less than $half_a_jump_block too because that would have been covered by the < 11 above
                        #if not less than five then we have a right bonus of 0 to $half_a_jump_block because right distance _might_ be less than $half_a_jump_block 
                        if ( $left_distance <= $half_a_jump_block )
                        {
                            $jump_pages = array_merge($jump_pages, range(1, $this->page_no));
                        }
                        else
                        {
                            $jump_pages[] = 1;
                            $jump_pages[] = -999;
                            #what $half_a_jump_block-2 means is it accounts for the ellipses: since this else is used when the left distance is larger than half a jump block there are going to be ellipses
                            $jump_pages = array_merge($jump_pages, range($this->page_no - ($half_a_jump_block-2) - $left_bonus, $this->page_no));
                        }

                        if ($this->page_no < $this->highest_page_no) { #obviously if we're on the last page than there is no right side
                            if ( $right_distance <= $half_a_jump_block ) {
                                $jump_pages = array_merge($jump_pages, range($this->page_no+1, $this->highest_page_no));
                            }
                            else
                            {
                                $jump_pages = array_merge($jump_pages, range( $this->page_no+1, $this->page_no + ($half_a_jump_block-2) + $right_bonus));
                                $jump_pages[] = -999;
                                $jump_pages[] = $this->highest_page_no;
                            }
                        }
                    }

                    for ($i = 0; $i < sizeof($jump_pages); $i++) {
                        if ($jump_pages[$i] == $this->page_no) {
                            ?><span class="paging_page_this_page"><?=$this->page_no;?></span><?
                        }
                        elseif ($jump_pages[$i] == -999) {
                            ?><span>&#8230;</span><?
                        }
                        else {
                            $this->link_to_page($jump_pages[$i], 'paging_page_jump_button');
                        }
                    }
                }

            ?></div><?

        if ($return_as_string) { $result = ob_get_contents(); ob_clean(); return $result; }
    }

    function link_to_page($page_no, $class=null, $link_text = null, $callback = null) {
        if ($this->callbacks && $callback && isset($this->callbacks[$callback])) {
            ?><a href="" class="<?=$class;?>" onclick="<?=$this->callbacks[$callback];?>();return false;"><?
        }
        else {
            ?><a class="<?=$class;?>" href="<?=page_parameters('/^page_no$/');?>&amp;page_no=<?=$page_no;?>"><?
        }
        if ($link_text) { echo $link_text; } else { echo $page_no; }
        ?></a><?
    }
}
?>
