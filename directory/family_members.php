<?php
session_start();

if(isset($_POST['id'])){
    $_SESSION['id']=$_POST['id'];
    
}
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Family Members</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <script>
        $(document).ready(function() {          
            loadData(1); // Function to load data for a specific page       
        });  
        

        // function for family search start
        function searchFamily(){
            if ($('#txt_search').val().trim()=='') {
                
                alertwarning('Please enter a value.');
                return;
            }       
            loadData(1);
        }
        // function for family search end

        // function to load family members start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/family_members.php",
               data: {
               action: 'load_data',
               page: page, 
               val:$('#txt_search').val()
               },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+ "<div class='row' >";
                    for (var i = 0; i < obj[1].length; i++) {
                        htm=htm+"<div class='col-lg-4'>";
                        htm=htm+"<div class='contact-box'>";
                        htm=htm+"<div class='col-sm-4'>";
                        htm=htm+"<div class='text-center'>";
                        htm=htm+"<img alt='image' class='img-circle m-t-xs img-responsive' src='../image_upload/members/thumbnails/"+obj[1][i].img+"'>";
                        htm=htm+"</div>";
                        htm=htm+"</div>";
                        htm=htm+"<div class='col-sm-8'>";
                        htm=htm+"<p class='m-b-xs'><strong>"+obj[1][i].first_name+" " + obj[1][i].middle_name + " " + obj[1][i].last_name + "</strong></p>";
                        htm=htm+ "<button type='button' class='btn btn-default btn-xs' onclick=\"window.location.href='tel:"+obj[1][i].phone+"'\"><i class='fa fa-phone'></i>Call</button>";
                        htm=htm+ "<button type='button' class='btn btn-default btn-xs' onclick=\"window.open('https://wa.me/"+obj[1][i].whtsapp+"', '_blank')\"><i class='fa fa-whatsapp'></i>Whatsapp</button>";
                        htm=htm+"<p><b>Email : </b><a href='mailto:"+obj[1][i].email+"'>"+obj[1][i].email+"</a></p>";
                        htm=htm+ "<button type='button' class='fa fa-trash btn btn-xs btn-danger' onclick='removeMember("+obj[1][i].id+");'>Remove</button>";
                        htm=htm+"</div>";
                        htm=htm+"<div class='clearfix'></div>";
                        htm=htm+"</div>";
                        htm=htm+"</div>";       
                    }                
                    htm=htm+ "</div>";

                    $('#table_client').html(htm);
                    var htmpage= paginate(totalrows,page);
                    $('#table_client').append(htmpage);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
        }
        // function to load family members end

    </script>

    <script>
        
        // function to remove member from a family start
        function removeMember(id,) {
            
            $("#hdn_id").val(id);
            deleteMember();
        }
        
        function deleteMember() {
            swal({
                title: "Are you sure?",
                text: "Do you want to save this data!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes,Remove!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm){
                    var data = {
                        action: 'remove_member',
                        id: $("#hdn_id").val(),               
                    };
                    // AJAX call
                    $.ajax({
                        type: "POST",
                        url: "api/family_members.php",
                        data: data,
                        success: function(response) {
                            $("#hdn_id").val(0);
                            console.log('deleted:', response);
                            alertwarning('Removed');
                            loadData($('#hdn_current_page').val());
                            
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX error:', status, error);
                        }
                    });
                        
                }
		    });   
        }
        // function to delete member from a family end

    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page"  value="0">
    <input type="hidden" id="hdn_id"  value="0">

    <div id="wrapper">

        <!-- navigation start -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="dropdown profile-element">
                <center>
                    <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px;"/></span>
                    <span class="clear"> <span class="block m-t-xs"> <strong class="font-bold"><?php echo $_SESSION['name']; ?></strong>
                </center>
            </div>
            <div class="sidebar-collapse" id="divMenuContainer">
                <!-- menu injected via ajax -->
            </div>
        </nav>
        <!-- navigation end -->
        <div id="page-wrapper" class="gray-bg">
            <!-- header start -->
            <div class="row border-bottom">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                    <div class="navbar-header">
                        <a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
                        
                    </div>
                    <ul class="nav navbar-top-links navbar-right">     
                        <form action="../app_login_manager/logout.php" method="post"></form>        
                            <li>
                                <a href="../app_login_manager/logout.php" style="color: #147ad1";>
                                    <i class="fa fa-sign-out"></i> Log out
                                </a>
                            </li>
                        </form>
                    </ul>

                </nav>
            </div>
            <!-- header end -->
            <!-- search bar starts -->
            <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-sm-4">
                    <h2>Family members</h2>
                    <div class="search-form">
                        <form action="index.html" method="get">
                            <div class="input-group">
                                <input type="text" placeholder="Search" id="txt_search" name="search" class="form-control">
                                <div class="input-group-btn">
                                    <button class="btn btn-white" onclick="searchFamily()" type="button">Search</button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
            <!-- search bar ends -->
            
            <div class="wrapper wrapper-content animated fadeInRight" id="table_client">
                <!-- data injected Dynamically via ajax -->
            </div>
        </div>
        

    </div>
       

    <!-- Mainly scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <!-- Custom and plugin javascript -->
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>


</body>

</html>
