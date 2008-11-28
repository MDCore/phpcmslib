function select_all_rows()
{
    var all = $('.delete_row');

    if ($('#delete_all').attr('checked') == true) { all.attr('checked', true); } else { all.attr('checked', false ); }
}


/* ajax functions */

