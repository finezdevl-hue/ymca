<?php
function get_pagination($totalRows, $current_page){
    //echo "get pagination started";
    
    $rowsPerPage=5;
    $pagesToShow = 5;
    // $totalRowsResult = app_exec_;
    // echo "total rows" .$totalRowsResult;
    // $totalRows = $totalRowsResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $rowsPerPage);   
    $currentGroup = ceil($current_page / $pagesToShow);
    $startPage = ($currentGroup - 1) * $pagesToShow + 1;
    $endPage = min($startPage + $pagesToShow - 1, $totalPages);
    $val;

echo "<div class='pagination' id='pagination-container'>";
if ($current_page > 1) {
    // echo "<a href='?page=" . ($current_page - 1) . "' class='pagination-link'>Previous</a>";
    $val= $current_page - 1;
    echo "<a onclick='loadClients(".$val.")' class='pagination-link'>Previous</a>";
}
for ($i = $startPage; $i <= $endPage; $i++) {
    $activeClass = ($i == $current_page) ? "class='active'" : "";
    echo "<a onclick='loadClients(".$i.")'  $activeClass>$i</a>"; //href='?page=$i'
}
if ($current_page < $totalPages) {
    //echo "<a href='?page=" . ($current_page + 1) . "' class='pagination-link'>Next</a>";
    $val= $current_page + 1;
    echo "<a onclick='loadClients(".$val.")' class='pagination-link'>Next</a>";
}
echo "</div>";

//$conn->close();

}
?>