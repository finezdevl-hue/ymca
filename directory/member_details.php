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

    <title>Members Details</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif !important;
            background-color: #f3f7ff !important;
        }
        .profile-card-container {
            max-width: 900px;
            margin: 30px auto;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04), 0 1px 8px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.7);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .profile-card-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.06);
        }
        .profile-left-panel {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 40px 30px;
            color: #ffffff;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            min-height: 480px;
        }
        .profile-avatar-wrapper {
            position: relative;
            margin-bottom: 20px;
        }
        .profile-avatar {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .profile-avatar:hover {
            transform: scale(1.05);
            border-color: rgba(255, 255, 255, 0.6);
        }
        .profile-name {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
            line-height: 1.3;
        }
        .profile-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 10px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .status-active {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        .status-inactive {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        .profile-right-panel {
            padding: 40px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            color: #8fa0c0;
            letter-spacing: 1px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px;
        }
        .section-title i {
            font-size: 15px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px 25px;
            margin-bottom: 30px;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f8fafc;
        }
        .detail-label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-size: 15px;
            font-weight: 500;
            color: #1e293b;
        }
        .detail-value a {
            color: #2563eb;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .detail-value a:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }
        .address-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #f1f5f9;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        .address-icon {
            background: #e2e8f0;
            color: #64748b;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }
        .address-text {
            font-size: 14px;
            line-height: 1.5;
            color: #475569;
            font-weight: 500;
        }
        .actions-wrapper {
            display: flex;
            gap: 15px;
        }
        .btn-action {
            flex: 1;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }
        .btn-call {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }
        .btn-call:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.25);
            filter: brightness(1.05);
        }
        .btn-whatsapp {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(16, 185, 129, 0.25);
            filter: brightness(1.05);
        }
        .btn-action:active {
            transform: translateY(1px);
        }

        /* Responsive styling */
        @media (max-width: 768px) {
            .profile-card-container {
                margin: 15px auto;
                border-radius: 16px;
            }
            .profile-left-panel {
                min-height: auto;
                padding: 30px 20px;
            }
            .profile-right-panel {
                padding: 30px 20px;
            }
            .details-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .actions-wrapper {
                flex-direction: column;
            }
        }
    </style>
    <script>
        $(document).ready(function() {          
            loadData(1); // Function to load data for a specific page       
        });  
        

        // function to load details of member strats
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/member_details.php",
               data: {
               action: 'load_member_data',
               page: page, 
               val:$('#txt_search').val()
               },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    var totalrows = obj[0].total_rows;
                    var htm="";
                    
                    for (var i = 0; i < obj[1].length; i++) {
                        var member = obj[1][i];
                        var statusClass = member.inactive == 1 ? 'status-inactive' : 'status-active';
                        var statusText = member.inactive == 1 ? 'Inactive' : 'Active';
                        var imgPath = member.img ? '../image_upload/members/uploads/' + member.img : '../img/customer.png';
                        
                        htm += "<div class='profile-card-container'>";
                        htm += "  <div class='row' style='margin: 0;'>";
                        
                        // Left Panel (Avatar & Name)
                        htm += "    <div class='col-md-4' style='padding: 0;'>";
                        htm += "      <div class='profile-left-panel'>";
                        htm += "        <div class='profile-avatar-wrapper'>";
                        htm += "          <img src='" + imgPath + "' alt='Profile' class='profile-avatar'>";
                        htm += "        </div>";
                        htm += "        <div class='profile-name'>" + member.first_name + " " + (member.middle_name ? member.middle_name + " " : "") + member.last_name + "</div>";
                        htm += "        <div class='profile-status-badge " + statusClass + "'><i class='fa fa-circle' style='font-size: 8px; margin-right: 6px;'></i>" + statusText + "</div>";
                        htm += "      </div>";
                        htm += "    </div>";
                        
                        // Right Panel (Details Grid)
                        htm += "    <div class='col-md-8' style='padding: 0;'>";
                        htm += "      <div class='profile-right-panel'>";
                        
                        // Section 1: Personal Info
                        htm += "        <div class='section-title'><i class='fa fa-user-circle-o'></i> Personal Information</div>";
                        htm += "        <div class='details-grid'>";
                        htm += "          <div class='detail-item'><span class='detail-label'>Gender</span><span class='detail-value'>" + (member.gender || 'N/A') + "</span></div>";
                        htm += "          <div class='detail-item'><span class='detail-label'>Date of Birth</span><span class='detail-value'>" + (member.dob || 'N/A') + "</span></div>";
                        htm += "          <div class='detail-item'><span class='detail-label'>Father's Name</span><span class='detail-value'>" + (member.father_name || 'N/A') + "</span></div>";
                        htm += "          <div class='detail-item'><span class='detail-label'>Mother's Name</span><span class='detail-value'>" + (member.mother_name || 'N/A') + "</span></div>";
                        htm += "          <div class='detail-item'><span class='detail-label'>Blood Group</span><span class='detail-value'>" + (member.blood_group || 'N/A') + "</span></div>";
                        htm += "          <div class='detail-item'><span class='detail-label'>Email Address</span><span class='detail-value'><a href='mailto:" + member.email + "'><i class='fa fa-envelope-o' style='margin-right: 5px;'></i>" + (member.email || 'N/A') + "</a></span></div>";
                        htm += "        </div>";
                        
                        // Section 2: Contact & Address
                        htm += "        <div class='section-title'><i class='fa fa-map-marker'></i> Contact & Address</div>";
                        
                        var addressParts = [];
                        if (member.p_street && member.p_street !== '0') addressParts.push(member.p_street.trim());
                        if (member.p_city && member.p_city !== '0') addressParts.push(member.p_city.trim());
                        if (member.p_pincode && member.p_pincode !== '0') addressParts.push(member.p_pincode);
                        if (member.p_country && member.p_country !== '0') addressParts.push(member.p_country.trim());
                        var fullAddress = addressParts.length ? addressParts.join(', ') : 'No Address provided';
                        
                        htm += "        <div class='address-box'>";
                        htm += "          <div class='address-icon'><i class='fa fa-location-arrow'></i></div>";
                        htm += "          <div class='address-text'>" + fullAddress + "</div>";
                        htm += "        </div>";
                        
                        // Section 3: Action Buttons
                        htm += "        <div class='actions-wrapper'>";
                        if (member.phone) {
                            htm += "          <a href='tel:" + member.phone + "' class='btn-action btn-call'><i class='fa fa-phone'></i> Call " + member.phone + "</a>";
                        }
                        if (member.whtsapp) {
                            htm += "          <a href='https://wa.me/" + member.whtsapp + "' target='_blank' class='btn-action btn-whatsapp'><i class='fa fa-whatsapp'></i> WhatsApp " + member.whtsapp + "</a>";
                        }
                        htm += "        </div>";
                        
                        htm += "      </div>"; // end profile-right-panel
                        htm += "    </div>"; // end col-md-8
                        
                        htm += "  </div>"; // end row
                        htm += "</div>"; // end profile-card-container
                    }                

                    $('#table_client').html(htm);
                    
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
            loadMenu()
        }
        // function to load details of member ends

      </script>
    <!-- <script src="../app_pagination/pagination.js"></script> -->
    <script src="../app_menu/menu.js"></script>
    
</head>

<body>
    <!-- <input type="hidden" id="hdn_current_page"  value="0">
    <input type="hidden" id="hdn_website_id"  value="0"> -->

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
