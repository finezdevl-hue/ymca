<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Attendance Details</title>

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
            loadData(); // Function to load data for a specific page       
        });  

        // function to search by date start
        function searchDate(){
            if ($('#date_search').val().trim()=='') {
                
                alertwarning('Please enter a date.');
                return;
            }       
            loadData();
        }
        // function to search by date end

        // function to load attendance details of the user who logged in start
        function loadData() {
            $('#hdn_current_page').val(); //used for Status Update function
            console.log("Loading data for page:", );
            $.ajax({               
                type: "POST",
                url: "api/attendance_details.php",
                data: {
                    action: 'load_data',
                    val:$('#date_search').val(),
                },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                  
                    var htm="";
                    htm=htm+ "<div class='row'>";

                    htm=htm+ "<div class='col-lg-12'>";
                    htm=htm+ "<div class='ibox float-e-margins'>";
                    htm=htm+ "<div class='ibox-title'>";
                    htm=htm+ "<h2><b><?php echo $_SESSION['name'];?></b></h2>";
                    htm=htm+ "<div class='ibox-tools'></div>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>SlNo</th>";
                    htm=htm+ "<th>DATE</th>";
                    htm=htm+ "<th>ATTENDANCE</th>";
                    htm=htm+ "<th>Action</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[0].length; i++) {
                        var j= i+1;
                        htm=htm+ "<tr>";
                        htm=htm+ "<td>"+j+"</td>";
                        htm=htm+ "<td>"+obj[0][i].month_year+"</td>";
                        htm=htm+ "<td><b>"+obj[0][i].total_attendance+"</b></td>";
                        htm=htm+ "<td><button class='btn btn-info ' type='button' onclick='showPayment(\""+obj[0][i].month_year+"\")'><i class='fa fa-paste'></i> View</button></td>";
                        htm=htm+ "</tr>";       
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";

                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";

                    $('#table_client').html(htm);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
          
        }
        // function to load attendance details of the user who logged in end

        // function for the popp to show the date of attendance marked start
        function showPayment(month_year){
            loadAttendance(month_year);
            $('#attendanceModel').modal('show');
        }
        // function for the popup to show the date of attendance marked 

        // function to close the popup start
        function closeAttendance(){
            $('#attendance')[0].reset();
            // $("hdn_id").val(0);
            $('#attendanceModel').modal('toggle');
        }
        // function to close the pop up end

        // function to load date of attendance marked for the user who logged in start
        function loadAttendance(month_year) {
            $('#hdn_current_page').val(); //used for Status Update function
            console.log("Loading data");
            $.ajax({               
                type: "POST",
                url: "api/attendance_details.php",
                data: {
                    action: 'load_attendance',
                    val: month_year,
                },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    // var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+ "<div class='row' style='padding-bottom: 15px;'>";
                    for (var i = 0; i < obj[0].length; i++) {
                        
                        htm=htm+ "<div class='col-lg-3 col-md-4 col-sm-4' style='padding-bottom: 15px;'><input type='text' value='"+obj[0][i].date+"' class='form-control' readonly></div>";      
                    }    
                    htm=htm+ "</div>";            
                    $('#total_attendance').html(htm);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
        }
        // function to load date of attendance marked for the user who logged in end
        
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
                    <div class="search-form">
                        <form action="index.html" method="get">
                            <div class="input-group">
                                <input type="month" placeholder="Search" id="date_search" name="date_search" class="form-control">
                                <div class="input-group-btn">
                                    <button class="btn btn-white" onclick="searchDate()" type="button">Search</button>
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

            <!-- popu modal for show the attendance marked for the user who logged in start -->
            <div class="modal inmodal" id="attendanceModel" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content animated bounceInRight">
                        <form method="POST" id="attendance">
                            <div class="modal-body">
                                <div id="total_attendance">
                                    <!-- dates of attendance marked injected via ajax -->
                                </div>
                            </div>
                            <div class="modal-footer">
                            <button type="button" class="btn btn-white" onclick="closeAttendance();">Close</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- popup modal for show the attendance marked for the user who logged in end -->

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
