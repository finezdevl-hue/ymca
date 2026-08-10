<?php
session_start();
    include '../app_common/enums.php';
// if(isset($_POST['id'])){
//     $_SESSION['id']=$_POST['id'];
// }
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Previous year Transactions</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
    <link href="../image_upload/members/upload.css" rel="stylesheet">
    
  
    <script>
        $(document).ready(function() {          
            loadData(1); // Function to load data for a specific page       
        });  
        
        // search box for members starts
        function searchMembers(){
            if ($('#txt_search').val().trim()=='') {
                
                alertwarning('Please enter a value.');
                return;
            }       
            loadData(1);
        }
        //search box for members ends

        // function to load all the members start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/previous_year_trans.php",
               data: {
               action: 'load_members_data',
               page: page, 
               val:$('#txt_search').val()
               },
                success: function(data) {  
                     
                    var obj = jQuery.parseJSON(data);
                    var totalrows = obj[0].total_rows;
                    // console.log (obj);
                    var htm="";
                    
                    htm=htm+ "<div class='col-lg-12'>";
                    htm=htm+ "<div class='ibox float-e-margins'>";
                    htm=htm+ "<div class='ibox-title'>";
                    htm=htm+ "<div class='ibox-tools'></div>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>No</th>";
                    htm=htm+ "<th>Member</th>";
                    htm=htm+ "<th>Name</th>";
                    

                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {
                        var j= i+1;
                        var slno=((page-1)*8)+j
                        
                        htm=htm+ "<tr>";
                        htm=htm+ "<td>" + slno+ "</td>";
                        htm=htm+ "<td><img alt='image' src='../image_upload/members/thumbnails/"+obj[1][i].img+"' style='width: 100px; height: 100px;'></td>";
                        htm=htm+ "<td>"+obj[1][i].first_name+" " + obj[1][i].middle_name + " " + obj[1][i].last_name + "</td>";
                       
                        htm=htm+ "<td><a onclick='addFees("+obj[1][i].id+",\"" + obj[1][i].first_name + "\",\"" + obj[1][i].middle_name + "\",\"" + obj[1][i].last_name + "\");' class='btn btn-default btn-xs'><i class='fa fa-money'></i>History</a></td>";
                        htm=htm+ "</tr>";
                    }                
                    
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    $('#table_members').html(htm);
                    var htmpage= paginate(totalrows,page);
                    $('#table_members').append(htmpage);
                },

                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
        }
        // function to load all the members end

        // function to navigate to see the previous year transaction history start
        function addFees(id,first_name,middle_name,last_name){ 
            $.post("prev_member_trans.php", { 'member_id': id,'first_name':first_name, 'middle_name':middle_name, 'last_name':last_name })
            .done(function(response) {
                window.location.href = "prev_member_trans.php";
            })
        }
        // function to navigate to see the previous year transaction history end
       
    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>

</head>

<body>
<!-- hidden values -->
<input type="hidden" id="hdn_current_page"  value="0">
<input type="hidden" id="hdn_id"  value="0">
<input type="hidden" id="hdn_member_id"  value="0">
<input type="hidden" id="hdn_name"  value="0">

    <div id="wrapper">
        <!-- navigation starts -->
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
        <!-- navigation starts -->
        <div id="page-wrapper" class="gray-bg">
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

            <!-- search bar started -->
            <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-sm-8">
                    <div class="search-form">
                        <h2>Member Transactions</h2>
                            <form action="index.html" method="get">
                                <div class="input-group">
                                    <input type="text" placeholder="Search" id="txt_search" name="search" class="form-control">
                                    <div class="input-group-btn">
                                        <button class="btn btn-white" onclick="searchMembers()" type="button">Search</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- search bar end -->
                
                <div class="wrapper wrapper-content animated fadeInRight" id="table_members">
                    <!-- Data injected by ajax -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mainly scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>
    <!-- Custom and plugin javascript -->
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../image_upload/members/image_upload.js"></script>
    <script src="../app_js/validation.js"></script>
    <script src="../js/loadingoverlay.min.js"></script>

</body>

</html>
