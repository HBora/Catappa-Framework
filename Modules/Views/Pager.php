<?php
$url = $_SERVER['REQUEST_URI'];
if (isset($_GET["page"]))
    $s_id = $_GET['page'];
else
    $s_id = 1;
$url = str_replace(array("?page=$s_id"), "", $url);
$pages = ceil($this->total_items / $this->page_size);
?>
<ul class="pagination">
    <?php for ($i = 1; $i <= $pages; $i++) : ?>
        <?php if ($s_id == $i) : ?>
            <li class="active"><a   href="<?php echo $url . "?page=$i" ?>"><?php echo $i ?></a></li>
            <?php else: ?>
            <li><a  href="<?php echo $url . "?page=$i" ?>"><?php echo $i ?></a></li>
        <?php endif; ?>
        <?php
    endfor;
    ?>
</ul>
