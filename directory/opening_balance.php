<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Opening Balance</title>

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
            loadData(1); // Function to load opening balance by year       
        });  

        // function to load Opening Balance start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/opening_balance.php",
               data: {
                action: 'load_data',
                page: page, 
                // val:$('#txt_search').val()
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
                    htm=htm+ "<th>Date</th>";
                    // htm=htm+ "<th>To Date</th>";
                    htm=htm+ "<th>Amount</th>";
                    htm=htm+ "<th>Year</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {

                        htm=htm+ "<tr>";
                        htm=htm+ "<td>"+obj[1][i].date+"</td>";
                        htm=htm+ "<td>"+obj[1][i].amount+"</td>";
                        htm=htm+ "<td>"+obj[1][i].from_year+"-"+obj[1][i].to_year+"</td>";
                        htm=htm+ "<td><button type='button' class='fa fa-edit btn btn-primary btn-xs' onclick='updatePayment("+obj[1][i].id+",\"" +obj[1][i].date+ "\",\"" +obj[1][i].amount+ "\");'>Edit</button>";
                        if(obj[1][i].isactive==1){
                            htm=htm+ "<button type='button' class='btn btn-success btn-xs' >Active</button></td>";

                        }
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
            load_closing_years();
            loadMenu();
        }
        // function to load Opening Balance end end
        
    </script>

    <script>

        // function for popup to edit Opening Balance  start
        function updatePayment(id,date,amount){
            $("#date").val(date);
            $("#amount").val(amount);
            $("#hdn_id").val(id);
            $('#paymentModel').modal('show');
        }
        // function for popup to edit Opening Balance end

        //function for poup to add new Openig Balance start
        function addPayment(){
            $('#paymentModel').modal('show');
        }
        //function for pop to add new Opening Balance end

        //function for close the popup for add new Opening Balance start
        function closeaddPayment(){
            $('#payment_form')[0].reset();
            $('hdn_id').val(0);
            $('#paymentModel').modal('toggle');
        }
        // function for close the popup for add new Opening Balance end

        //function to save new Opening Balance or update start
        function savePayment() {
            swal({
                title: "Are you sure?",
                text: "Do you want to save this data!",
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
                var data = {
                    action: 'save_payment',
                    date: $('#date').val(),  
                    // to_date: $('#to_date').val(),
                    amount: $('#amount').val(),
                    flag: $("#selected_year").val(),
                    id: $("#hdn_id").val(),
                };
                // AJAX call
                load_overlay();
                $.ajax({
                    type: "POST",
                    url: "api/opening_balance.php",
                    data: data,
                    success: function(response) {
                        close_overlay();
                        console.log('saved:', response);                    
                        closeaddPayment();
                        alertsuccess('Saved Sucessfully');
                        loadData($('#hdn_current_page').val());
                    },
                    error: function (xhr, status){
        
                        var msgObj = JSON.parse(xhr.responseText);
                        alerterror(msgObj, xhr);
                        $('#payment_form')[0].reset();
                        $('hdn_id').val(0);
                    
                    }
                });
            }
		    });
        }
        // function to save new Opening Balance or update end
        
        //function to delete Opening Balnce details start
        function deletePaymentDetails(id) {
            $("#hdn_id").val(id);
            deleteRow();
        }

        function load_closing_years(){
            $.ajax({
   
                type: "POST",
                url: "api/opening_balance.php",
                data: {
                action: 'load_closing_years',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    // console.log(obj);
                                       
                    // var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+ "<div class='dropdown form-group'><select id='selected_year' class='form-control'>";
                    // htm=htm+"<option  value='0' selected disabled>select head</option>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+"<option  value='"+obj[0][i].id+"'>"+obj[0][i].from_year+" - "+obj[0][i].to_year+"</option>";
                    }                
                    htm=htm+"</select></div>";

                   

                    $('#select_from_year').html(htm);// Inject the data into the container
                   
                    
                    // loadData(1);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }
        
        function deleteRow() {
            swal({
                title: "Are you sure?",
                text: "Do you want to save this data!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes,delete!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
               function (isConfirm) {
			if (isConfirm){
                var data = {
                action: 'delete_payment_details',
                id: $("#hdn_id").val(),               
            };
            // AJAX call
            load_overlay();
            $.ajax({
                type: "POST",
                url: "api/opening_balance.php",
                data: data,
                success: function(response) {
                    close_overlay();
                    $("#hdn_id").val(0);
                    // console.log('deleted:', response);
                    alertwarning('Deleted');
                    loadData($('#hdn_current_page').val());
                    
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', status, error);
                }
            });
                
                    
            }
		});   
        }
        //function to delete Opening Balance end
    
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
                <div class="col-sm-8">
                    <h2>Opening Balance</h2>
                </div>
                <div class="col-sm-4">
                    <div class="title-action">
                        <div class="ibox-tools">
                            <button type="button" class="btn btn-primary btn-xs" onclick="addPayment(0)">Add</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- search bar ends -->
            
            <div class="wrapper wrapper-content animated fadeInRight" id="table_client">
                <!-- data injected Dynamically via ajax -->
            </div>
        </div>
    
        <!-- popup modal for add new payment into master starts -->
        <div class="modal inmodal" id="paymentModel" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight">
                    <form method="POST" id="payment_form">
                        <div class="modal-body">
                            <div class="form-group"><label>Date</label><input type="date" id="date" name="date" placeholder="Date"  class="form-control"></div>
                            <!-- <div class="form-group"><label>To Date</label><input type="date" id="to_date" name="to_date" placeholder="To Date"  class="form-control"></div> -->
                            <div class="form-group"><label>Amout</label><input type="text" id="amount" name="amount" placeholder="Amount"  class="form-control"></div>
                            <div id="select_from_year">
                                <!-- dropdown injected via ajax -->
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" onclick="closeaddPayment();">Close</button>
                            <button type="button" class="btn btn-primary" onclick="savePayment();">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- popup modal for add new payment into master ends -->
    </div>
       

    <!-- Mainly scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <!-- Custom and plugin javascript -->
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../js/loadingoverlay.min.js"></script>


</body>

</html>
