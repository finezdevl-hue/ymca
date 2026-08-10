<?php
session_start();

if(isset($_POST['member_id'])){
    $_SESSION['member_id']=$_POST['member_id'];
    
}
if(isset($_POST['first_name'])){
    $_SESSION['first_name']=$_POST['first_name'];
    
}
if(isset($_POST['middle_name'])){
    $_SESSION['middle_name']=$_POST['middle_name'];
    
}
if(isset($_POST['last_name'])){
    $_SESSION['last_name']=$_POST['last_name'];
    
}
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Member Transactions</title>

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
        
        // function to load all previous year fee details of the member start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/prev_member_trans.php",
               data: {
               action: 'load_data',
               page: page, 
                //    val:$('#txt_search').val()
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
                    htm=htm+ "<div class='ibox-tools'></div>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>No</th>";
                    htm=htm+ "<th>Date</th>";
                    htm=htm+ "<th>Fees</th>";
                    htm=htm+ "<th>Transaction Type</th>";
                    htm=htm+ "<th>Head</th>";
                    htm=htm+ "<th>Discription</th>";
                    htm=htm+ "<th>Wallet</th>";
                    htm=htm+ "<th>Year</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {

                        let type = Number(obj[1][i].transaction_type);
                        let transaction = '';

                        if (type === 1) {
                            transaction = 'cash';
                        } else if (type === 2) {
                            transaction = 'bank';
                        } else {
                            transaction = 'Nil';
                        }

                        var j= i+1;
                        var slno=((page-1)*8)+j
                        htm=htm+ "<tr>";
                        htm=htm+ "<td>"+slno+"</td>";
                        htm=htm+ "<td>"+obj[1][i].date+"</td>";
                        htm=htm+ "<td>"+obj[1][i].fees+"</td>";
                        htm=htm+ "<td>"+transaction+"</td>";
                        htm=htm+ "<td>"+obj[1][i].head+"</td>";
                        htm=htm+ "<td>"+obj[1][i].discription+"</td>";
                        htm += "<td>" + (obj[1][i].iswallet == 0 ? "No" : "Yes") + "</td>";
                        htm=htm+ "<td>"+obj[1][i].from_year+" - "+obj[1][i].to_year+"</td>";
                        // htm=htm+ "<td><button type='button' class='fa fa-edit btn btn-primary btn-xs' onclick='addFees("+obj[1][i].id+",\"" +obj[1][i].date+ "\",\"" +obj[1][i].fees+ "\",\"" +obj[1][i].head_id+ "\",\"" +obj[1][i].discription+ "\");'>Edit</button>";
                        // htm=htm+ "<td><button type='button' class='fa fa-trash btn btn-danger btn-xs' onclick='deleteFeeDetails("+obj[1][i].id+"," +obj[1][i].fees+"," +obj[1][i].receiveble_id+");'>Delete</button></td>";
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
                    var htmpage= paginate(totalrows,page);
                    $('#table_client').append(htmpage);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
        }
        // function to load all previous year fee details of the member end

    </script>

    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page"  value="0">
    <input type="hidden" id="hdn_id"  value="0">
    <input type="hidden" id="hdn_amount"  value="0">
    <input type="hidden" id="hdn_receiveble_id"  value="0">

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
                    <h2><?php echo $_SESSION['first_name'] . " " . $_SESSION['middle_name'] . " " . $_SESSION['last_name']; ?></h2>
                    
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
