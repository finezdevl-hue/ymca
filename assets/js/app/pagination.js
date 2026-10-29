function paginate(totalRows, current_page) {
    $('#hdn_current_page').val(current_page);
    var rowsPerPage=8;
    var pagesToShow = 5;
    var totalPages = Math.ceil(totalRows / rowsPerPage);   
    var currentGroup = Math.ceil(current_page / pagesToShow);
    var startPage = (currentGroup - 1) * pagesToShow + 1;
    var endPage = Math.min(startPage + pagesToShow - 1, totalPages);
    var val;

    var html='';
    html=html+"<div class='hr-line-dashed'></div>";
    html=html+ "<div class='text-center'>"
if (current_page > 1) {
    // echo "<a href='?page=" . ($current_page - 1) . "' class='pagination-link'>Previous</a>";
    var val= current_page - 1;
    html=html+ "<a onclick='loadData("+val+")'class='btn btn-white''><</a>";
}
for (var i = startPage; i <= endPage; i++) {
    var activeClass = (i == current_page) ? "active" : "";
    html=html+ "<a onclick='loadData("+i+")' class='btn btn-white " +activeClass+"'>"+i+"</a>"; //href='?page=$i'
}
if (current_page < totalPages) {
    //echo "<a href='?page=" . ($current_page + 1) . "' class='pagination-link'>Next</a>";
    val= current_page + 1;
    html=html+ "<a onclick='loadData("+val+")'class='btn btn-white''>></a>";
}
    html=html+ "</div>"

return html;

}

function updatePagination(currentPage) {
    // Remove active class from all links
    $('.btn btn-white').removeClass('active');

    // Add active class to the current page
    $('.btn btn-white').each(function() {
        var page = $(this).data('page');
        if (page == currentPage) {
            $(this).addClass('active');
        }
    });
}