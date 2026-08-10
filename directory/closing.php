<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Close Account</title>

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
            loadClosedData();    
            // Function to load data     
        });  

        // function to show the closed years start
        function loadClosedData() {
            $.ajax({               
                type: "POST",
                url: "api/closing.php",
                data: {
                    action: 'load_closed_date',
                },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+ "<div class='row'>";

                    htm=htm+ "<div class='col-lg-12'>";
                    htm=htm+ "<div class='ibox float-e-margins'>";
                    htm=htm+ "<div class='ibox-title'>";
                    htm=htm+ "<h2>Recieved And Recieveble</h2>";
                    htm=htm+ "<div class='ibox-tools'></div>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>No</th>";
                    htm=htm+ "<th>Closed Year</th>";
                    // htm=htm+ "<th>Balance</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    
                    for (var i = 0; i < obj[0].length; i++) {
                        var j = i+1;
                        htm=htm+ "<tr>";
                        htm=htm+ "<td>"+j+"</td>";
                        htm=htm+ "<td>"+obj[0][i].from_year+" -  "+obj[0][i].to_year+"</td>";
                        // htm=htm+ "<td>"+(obj[0][i].receivable_amount - obj[0][i].received_amount)+"</td>";
                        htm=htm+ "</tr>";
                        
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";

                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";

                    $('#table_fees').html(htm);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
        }
        // function to show the closed years

        // function to creat copy of all tables of payment start
        // function closeAccount() {
        //     $.ajax({               
        //        type: "POST",
        //        url: "api/closing.php",
        //        data: {
        //         action: 'close_account',
        //         // page: page, 
        //        },
        //         success: function(response) {
        //             // resetTables();
        //             alertsuccess(response)
        //             loadClosedData();
        //         },
        //         error: function(xhr, status, error) {
        //            console.log('AJAX error: ', status, error);
        //         }
        //     });
           
        // }
        // function to creat copy of all tables of payment end

        function closeAccount() { 
            swal({
                title: "Are you sure?",
                text: "Do you want to close the completed accounts!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes,Save!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
               function (isConfirm) {
			if (isConfirm){
                $.ajax({
                    type: "POST",
                    url: "api/closing.php",
                    data: {
                        action: 'close_account',
                    },
                    success: function(response) {
                        alertsuccess(response)
                        loadClosedData();
                    },
                    error: function (xhr, status){
                        var msgObj = JSON.parse(xhr.responseText);
                        alerterror(msgObj, xhr);
                    }
                });
            }
		    }); 
        }

        // function to reset the tables start
        function resetTables() {
            $.ajax({               
               type: "POST",
               url: "api/closing.php",
               data: {
                action: 'reset_tables',
                // page: page, 
               
               },
                success: function(response) {
                    alertsuccess(response);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
           
        }
        // function to reset the tables end

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
                <div class="col-sm-3">
                    <h2>Close Accounts</h2>
                </div>
               
                <div class="col-sm-9">
                    <div class="title-action">
                        <div class="ibox-tools">
                            <button type="button" class="btn btn-primary" onclick="closeAccount()">Close Account</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- search bar ends -->
             
            <div class="wrapper wrapper-content animated fadeInRight" id="table_payments">
                <!-- data of payments injected Dynamically via ajax -->
            </div>

            <div class="wrapper wrapper-content animated fadeInRight" id="table_fees">
                <!-- data of fees injected Dynamically via ajax -->
            </div>
            <!-- <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-sm-3">
                    <h2>Reset Tables</h2>
                </div>
               
                <div class="col-sm-9">
                    <div class="title-action">
                        <div class="ibox-tools">
                            <button type="button" class="btn btn-primary" onclick="resetTables();">Reset Now</button>
                        </div>
                    </div>
                </div>
            </div> -->

            <!-- <div class="wrapper wrapper-content animated fadeInRight" id="total_balance">
                
            </div> -->
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
