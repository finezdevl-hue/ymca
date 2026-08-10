<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Closing Years</title>

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

        // function to load Payment detail start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/closing_years.php",
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
                    htm=htm+ "<th>No</th>";
                    htm=htm+ "<th>NAME</th>";
                    htm=htm+ "<th>TYPE</th>";
                    htm=htm+ "<th>Action</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {

                        var j= i+1;
                        var slno=((page-1)*8)+j
                        
                        htm=htm+ "<tr>";
                        htm=htm+ "<td>" + slno+ "</td>";
                        htm=htm+ "<td>"+obj[1][i].from_year+"</td>";
                        htm=htm+ "<td>"+obj[1][i].to_year+"</td>";

                        htm=htm+ "<td><button type='button' class='fa fa-edit btn btn-primary btn-xs' onclick='updatePayment("+obj[1][i].id+",\"" +obj[1][i].from_year+ "\",\"" +obj[1][i].to_year+ "\");'>Edit</button>";
                        if(obj[1][i].hide==0){
                            htm=htm+ "<button type='button' class='fa  fa-eye-slash btn btn-danger btn-xs' onclick='hideClosingyear("+obj[1][i].id+",1);'>Hide</button></td>";
                        }
                        else{
                            htm=htm+ "<button type='button' class='fa  fa-eye btn btn-primary btn-xs' onclick='hideClosingyear("+obj[1][i].id+",0);'>Show</button></td>";
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
            loadMenu();
        }
        // function to load Payment details end
        
    </script>

    <script>

        // function for popup to edit Payment details  start
        function updatePayment(id,from_year,to_year){
          
            $("#from_year").val(from_year);
            $("#to_year").val(to_year);
            $("#hdn_id").val(id);
            $('#closing_yearModal').modal('show');
          
        }
        // function for popup to edit Payment details end

        //function for poup to add new Payment details into master table start
        function addClosingyear(){
            $('#closing_yearModal').modal('show');
        }
        //function for pop to add new Payment details into master table end

        //function for close the popup for add new Payment details start
        function closeAddyear(){
            $('#closingyear_form')[0].reset();
            $("#hdn_id").val(0);
            $('#closing_yearModal').modal('toggle');
        }
        // function for close the popup for add new Payment details end

        //function to save new payment details or update details start
        function saveClosingyear() {

            const from_year = $('#from_year').val();
            const to_year = $('#to_year').val();

         
            if (from_year == "") {
                alertinfo("From Year cannot be empty.");
                return;
            }
            
            if (to_year == "") {
                alertinfo("To Year cannot be empty.");
                return;
            }
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
                    action: 'save_year',
                    from_year: $('#from_year').val(),  
                    to_year: $("#to_year").val(),
                    id: $("#hdn_id").val(),
                };
                // AJAX call
                $.ajax({
                    type: "POST",
                    url: "api/closing_years.php",
                    data: data,
                    success: function(response) {
                        closeAddyear();
                        console.log('saved:', response);                    
                        alertsuccess('Saved Sucessfully');
                        loadData($('#hdn_current_page').val());
                    },
                    error: function (xhr, status){
        
                        var msgObj = JSON.parse(xhr.responseText);
                        alerterror(msgObj, xhr);
                        $('#closingyear_form')[0].reset();
                        $("hdn_id").val(0);
                    
                    }
                });
            }
		    });
        }
        // function to save new payment details or update details end
        
        //function to delete payment details start
        // function hideClosingyear(id,hide) {
        //     alert(hide);
        //     $("#hdn_id").val(id);
        //     deleteRow();
        // }
        
        function hideClosingyear(id,hide) {
            let text = '';
            let confirmButtonText = '';
            if (hide === 0) {
                text = 'Do you want to show the year?';
                confirmButtonText= 'Yes,Show!';
            } else {
                text = 'Do you want to Hide the year?';
                confirmButtonText= 'Yes,Hide!';
            }
            
            swal({
                title: "Are you sure?",
                text: text,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: confirmButtonText,
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
               function (isConfirm) {
			if (isConfirm){
                var data = {
                action: 'hide_closing_year',
                id: id,
                hide: hide,   
            };
            // AJAX call
            $.ajax({
                type: "POST",
                url: "api/closing_years.php",
                data: data,
                success: function(response) {
                    $("#hdn_id").val(0);
                    // console.log('deleted:', response);
                    alertsuccess('Updated');
                    loadData($('#hdn_current_page').val());
                    
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', status, error);
                }
            });
                
                    
            }
		});   
        }
        //function to delete payment details end

        
    
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
                    <h2>Closing Years</h2>
                </div>
                <div class="col-sm-4">
                    <div class="title-action">
                        <div class="ibox-tools">
                            <button type="button" class="btn btn-primary btn-xs" onclick="addClosingyear(0)">Add Years</button>
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
        <div class="modal inmodal" id="closing_yearModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight">
                    <form method="POST" id="closingyear_form">
                        <div class="modal-body">
                            <div class="form-group"><label>From Year</label><input type="number" id="from_year" name="from_year" placeholder="From Year"  class="form-control"></div>
                            <div class="form-group"><label>To Year</label><input type="number" id="to_year" name="from_year" placeholder="To Year"  class="form-control"></div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" onclick="closeAddyear();">Close</button>
                            <button type="button" class="btn btn-primary" onclick="saveClosingyear();">Save</button>
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


</body>

</html>
