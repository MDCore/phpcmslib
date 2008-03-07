<!doctype html public "-//w3c//dtd xhtml 1.0 transitional//en" "http://www.w3.org/tr/xhtml1/dtd/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title>pedantic system admin - migrate</title>
<?=default_scripts();?>
<style type="text/css">
body
{
    font-family: tahoma, verdana, arial;
}
.migration
{
    margin-bottom: 10px;
}
.migration h4
{
    margin: 0; padding: 0;
    font-weight: normal;
}
.migration a
{
    color: #000;
    font-size: 0.8em; 
    text-decoration: none;
}
.detail
{
    font-family: "courier new", monospace;
    display: none;
    border: 1px solid #ccc;
    font-size: 10pt;
    white-space: pre;
}
.detail div
{
    margin-bottom: 5px;
}
.success
{
    display: block;
    margin: 0; padding: 0;
}
</style>
<script type="text/javascript">
function show_hide_detail(detail_id)
{
    $('#'+detail_id).slidetoggle('fast');
}
</script>
</head>
<body>
    <h2><?=proper_nounize(APP_NAME);?> schema is currently at version <?=$schema_version;?></h2><?

    foreach ($sys->migrations as $migration)
    {
        if ($migration['version'] > $schema_version)
        {
            ?><div class="migration"><span class="description">version <?=$migration['version']?> - <i><?=$migration['description'];?></i></span>
            <a href="#" onclick="show_hide_detail('v_<?=$migration['version'];?>_detail'); return false;">(detail)</a>
                <div class="detail" id="v_<?=$migration['version'];?>_detail"><?
            ?></div>
            <span class="success">successfully migrated to version <?=$migration['version'];?></span>&nbsp;
            </div><?
        }
    }

    echo $schema_interrregator_results;
    #successfully completed
    ?><script type="text/javascript">document.title='<?=proper_nounize(APP_NAME);?> | migration complete';</script>
</body>
</html>
