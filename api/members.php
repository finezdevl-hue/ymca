<?php
session_start();
include_once __DIR__ . '/../app_common/db_connect.php';
include_once __DIR__ . '/../app_common/auth_helper.php';
include_once __DIR__ . '/../app_pagination/pagination.php';

$current_login = (int)($_SESSION['login_id'] ?? 0);
if (empty($current_login) || isNormalMember($current_login)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

    // action start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to save member details or update member details start
        if($action=="save_members"){
            try{
                $email = trim($_POST['email'] ?? '');
                $member_id = (int)($_POST['id'] ?? 0);

                if (!empty($email)) {
                    // Check if email is already in use by another member in tbl_members
                    $chk_sql = "SELECT id FROM tbl_members WHERE email = ? AND id != ?";
                    $chk_res = app_exec_getresult($chk_sql, [$email, $member_id], "si");
                    if ($chk_res && $chk_res->num_rows > 0) {
                        http_response_code(400);
                        header('Content-Type: application/json');
                        echo json_encode(['Message' => 'This email address is already in use by another member.']);
                        exit;
                    }

                    // Check if email is already registered to a different login account in tbl_login
                    if ($member_id > 0) {
                        $old_email_sql = "SELECT email FROM tbl_members WHERE id = ?";
                        $old_email_res = app_exec_getresult($old_email_sql, [$member_id], "i");
                        $old_email = '';
                        if ($old_email_res && $row = $old_email_res->fetch_assoc()) {
                            $old_email = $row['email'];
                        }

                        if (!empty($old_email) && $old_email !== $email) {
                            $chk_login_sql = "SELECT login_id FROM tbl_login WHERE email = ? AND email != ?";
                            $chk_login_res = app_exec_getresult($chk_login_sql, [$email, $old_email], "ss");
                            if ($chk_login_res && $chk_login_res->num_rows > 0) {
                                http_response_code(400);
                                header('Content-Type: application/json');
                                echo json_encode(['Message' => 'This email address is already registered to another login account.']);
                                exit;
                            }
                        }
                    } else {
                        $chk_login_sql = "SELECT login_id FROM tbl_login WHERE email = ?";
                        $chk_login_res = app_exec_getresult($chk_login_sql, [$email], "s");
                        if ($chk_login_res && $chk_login_res->num_rows > 0) {
                            http_response_code(400);
                            header('Content-Type: application/json');
                            echo json_encode(['Message' => 'This email address is already registered to another login account.']);
                            exit;
                        }
                    }
                }

                if($_POST['id']==0){
                    $sql ="INSERT INTO tbl_members (first_name, middle_name,last_name,father_name,mother_name,dob,
                    blood_group,phone,whtsapp,email,p_street,p_city,p_pincode,p_country,img) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $parameters = [
                    $_POST['first_name'],
                    $_POST['middle_name'],
                    $_POST['last_name'],

                    $_POST['father_name'],
                    $_POST['mother_name'],
                    $_POST['dob'],

                   
                   
                    $_POST['blood_group'],
                    $_POST['phone'],
                    $_POST['whtsapp'],
                    $_POST['email'],
                    


                    $_POST['p_street'],
                    $_POST['p_city'],
                    $_POST['p_pincode'],
                    $_POST['p_country'],

                    $_POST['img'],
                    ];

                    $types="ssssssissssisss";

                }

                else{
                    // 1. Get the old email address and existing image of this member before updating
                    $old_email_sql = "SELECT email, img FROM tbl_members WHERE id = ?";
                    $old_email_res = app_exec_getresult($old_email_sql, [$_POST['id']], "i");
                    $old_email = '';
                    $old_img = '';
                    if ($old_email_res && $row = $old_email_res->fetch_assoc()) {
                        $old_email = $row['email'];
                        $old_img = $row['img'];
                    }

                    $sql = "UPDATE tbl_members SET  first_name = ?, middle_name = ?, last_name = ? , father_name = ?, mother_name = ?, dob =?,
                     blood_group= ?, phone = ?, email = ?, p_street =  ?, p_city = ?, p_pincode = ?, p_country = ?, img = ?  WHERE id = ?";
                    
                    $parameters = [
                    $_POST['first_name'],
                    $_POST['middle_name'],
                    $_POST['last_name'],

                    $_POST['father_name'],
                    $_POST['mother_name'],
                    $_POST['dob'],
                    $_POST['blood_group'],

                    $_POST['phone'],
                    $_POST['email'],

                    $_POST['p_street'],
                    $_POST['p_city'],
                    $_POST['p_pincode'],
                    $_POST['p_country'],
                    $_POST['img'],

                    $_POST['id'],
                    ];

                    $types="ssssssissssissi";
                    app_exec_nonquery($sql, $parameters, $types);

                    // Delete old image from server disk if a new image was uploaded
                    $new_img = trim($_POST['img'] ?? '');
                    if (!empty($old_img) && !empty($new_img) && $old_img !== $new_img) {
                        deleteOldMemberImage($old_img);
                    }

                    // 2. If the email was changed, update the login table to match
                    if (!empty($old_email) && $old_email !== $_POST['email']) {
                        $login_id = null;
                        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $_POST['id']) {
                            $login_id = $_SESSION['login_id'] ?? null;
                            $_SESSION['email'] = $_POST['email'];
                        }

                        if (!empty($login_id)) {
                            $update_login_sql = "UPDATE tbl_login SET email = ? WHERE login_id = ?";
                            app_exec_nonquery($update_login_sql, [$_POST['email'], $login_id], "si");
                        } else {
                            $update_login_sql = "UPDATE tbl_login SET email = ? WHERE email = ?";
                            app_exec_nonquery($update_login_sql, [$_POST['email'], $old_email], "ss");
                        }
                    }
                }
            }
            // catch (Throwable $e) {
            //     throw new Exception('Oops! Something went wrong.');
            // }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }       
            
        }
        // action to save member details  or update member details end
             
        // action to load groups for the popup to add members into groups start
        if($action=="load_groups"){
            try{
                $sql = "SELECT id, name, status FROM tbl_groups WHERE status = 1";
                $result = app_exec_query($sql);
                
                echo"<span class='close' onclick='closegroupsModal()'>&times;</span>";
                echo"<h4>Select Group</h4>";
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $id = $row['id'];
                        $name = $row['name'];                
                        echo"<div class='i-checks'><label><input type='checkbox' name='group' id='$id' value='$id'><i></i>$name</label></div>";
                    }
                } else {

                    echo"<div class='i-checks'><label><input type='checkbox' value=''> <i></i>no groups available</label></div>";
                }
                echo "<button onclick='addMemberToGroups();' class='save-button'>Save</button>";
                
                exit();
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action to load groups for the popup to add members into groups end

        // action to fetch group details of a member start
        if($action=="fetch_group_details"){
            try{
                
                if (isset($_POST['id'])) {
                    
                    $sql = "SELECT  group_id FROM tbl_group_member_map WHERE member_id=" . $_POST['id'];
                    $result = app_exec_query($sql);
                    
                    // echo $result;
                }
                if ($result && $result->num_rows > 0) {
                    // Fetch all rows as an array
                    $data = [];
                    while ($row = $result->fetch_assoc()) {
                        $data[] = $row; // Add each row to the array
                    }
                } else {
                    $data = []; // If no data is found
                }
                
                
                echo json_encode($data);
                exit();
                
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action to fetch group details of a member end

        // action to save login for the members start
        if($action=="save_login"){
            try{
                $email = trim($_POST['email'] ?? '');
                $name = trim($_POST['name'] ?? '');
                $password = trim($_POST['password'] ?? '');

                if (empty($email)) {
                    http_response_code(400);
                    header('Content-Type: application/json');
                    echo json_encode(['Message' => 'Email address is required.']);
                    exit();
                }

                if (empty($password)) {
                    http_response_code(400);
                    header('Content-Type: application/json');
                    echo json_encode(['Message' => 'Password is required.']);
                    exit();
                }

                // Check if login already exists for this email
                $chk_sql = "SELECT login_id FROM tbl_login WHERE email = ?";
                $chk_res = app_exec_getresult($chk_sql, [$email], "s");

                if ($chk_res && $row = $chk_res->fetch_assoc()) {
                    // Existing login -> Update password & name
                    $upd_sql = "UPDATE tbl_login SET name = ?, password = ? WHERE login_id = ?";
                    app_exec_nonquery($upd_sql, [$name, $password, $row['login_id']], "ssi");
                    echo json_encode(['Message' => 'Password updated successfully.']);
                    exit();
                } else {
                    // New login -> Insert new record
                    $ins_sql = "INSERT INTO tbl_login (name, email, password) VALUES (?, ?, ?)";
                    app_exec_nonquery($ins_sql, [$name, $email, $password], "sss");
                    echo json_encode(['Message' => 'Login created successfully.']);
                    exit();
                }
            }
            catch (Exception $e) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'Message' => $e->getMessage(),
                ]);
                exit();
            }
        }
        // action to save login for the members end

        // function to add members into groups start
        if($action=="add_member_to_groups"){
            // echo "started";
    
            try{
                if (isset($_POST['id'])) { 
                    $sql = "DELETE FROM tbl_group_member_map WHERE member_id = ?";
                    $parameters = [
                        $_POST['id']
                       
                    ];
                    $types = "i";
                    $result = app_exec_nonquery($sql, $parameters, $types);
                    $group_ids = $_POST['group_ids']; 
                    $length = count($group_ids); 
                    
                    for ($i = 0; $i < $length; $i++) {
                        $current_group_id = $group_ids[$i]; 
                        echo $current_group_id;
                        $sql = "INSERT INTO tbl_group_member_map (member_id, group_id) VALUES (?, ?)";
                        $parameters = [
                            $_POST['id'],
                            $current_group_id,
                        ];
                        $types = "ii";
                        $result = app_exec_nonquery($sql, $parameters, $types);
                    }
                }
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
            // catch (Exception $e) {
            //     http_response_code(500);   
            //     echo "Oops! Something went wrong." . $e->getMessage();
            //     return;
            // } 
        }
        // action to add members into groups end

        // action to load members details start
        if($action=="load_members_data"){
            try{
                $rowsPerPage = 8;

                $current_page = (int)$_POST['page'];

                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
                
                $offset = ($current_page - 1) * $rowsPerPage;
                
                if($_POST['val']== ''){
                    // $sqldatarows = "SELECT f.id, f.first_name, f.middle_name, f.last_name, b.name AS blood_group FROM tbl_members AS f JOIN tbl_bloodgroup_master AS b ON f.blood_group = b.id";
                    $sqldatarows = "SELECT  id,first_name,last_name,middle_name,father_name,mother_name,dob,
                    blood_group,phone,whtsapp,email,p_street,p_city,p_pincode,p_country,img FROM tbl_members ";
                     
                    $sqlcountrows = "SELECT  Count(id) as total FROM tbl_members";
                }
                else{            

                    $sqldatarows = "SELECT  m.id,m.first_name,m.last_name,m.middle_name,m.father_name,m.mother_name,m.dob,
                    m.phone,m.whtsapp,m.email,m.p_street,m.p_city,m.p_pincode,m.p_country,m.img,b.id as blood_group,
                    b.name as bg_name FROM tbl_members as m LEFT JOIN tbl_bloodgroup_master as b ON m.blood_group = b.id 
                    WHERE m.first_name LIKE ? OR m.middle_name LIKE ? OR m.last_name like ? OR b.name = ?"; 

                    $sqlcountrows = "SELECT  Count(m.id) as total
                    FROM tbl_members as m LEFT JOIN tbl_bloodgroup_master as b ON m.blood_group=b.id WHERE m.first_name LIKE ? OR m.middle_name LIKE ? OR m.last_name like ? OR b.name = ?";         
                }    
                
                $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
            
                if($_POST['val']== '') {
                    $result = app_exec_query($sqldatarows);
                    
                    $totalRowsResult = app_exec_query($sqlcountrows);
                    $totalRows = $totalRowsResult->fetch_assoc()['total'];
                }
                else{
                    $searchvalue= $_POST['val'];
                    $parameters = [
                        '%' . $searchvalue . '%',
                        '%' . $searchvalue . '%',
                        '%' . $searchvalue . '%',
                         $searchvalue,
                    ];
                    
                    $types="ssss";
                    $result=app_exec_getresult($sqldatarows,$parameters,$types);

                    $totalRowsResult = app_exec_getresult($sqlcountrows,$parameters,$types);
                    $totalRows = $totalRowsResult->fetch_assoc()['total'];
                }

                //stringify
                if ($result && $result->num_rows > 0) {
                    // Fetch all rows as an array
                    $qrydata = [];
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row; // Add each row to the array
                    }
                } else {
                    $qrydata = []; // If no data is found
                }

                $pagination = array("total_rows"=>$totalRows);
                $resdata=array($pagination,$qrydata);

                echo json_encode($resdata);
                exit();
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
            
        }
        // action to load members details end

        // action to load blood groups for dropdown in the popup starts
        if($action=="load_blood_groups"){
            try{
                $sql = "SELECT id, name FROM tbl_bloodgroup_master";
                $result = app_exec_query($sql);

                if ($result && $result->num_rows > 0) {
                    // Fetch all rows as an array
                    $qrydata = [];
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row; // Add each row to the array
                    }
                } else {
                    $qrydata = []; // If no data is found
                }
        
                // $pagination = array("total_rows"=>$totalRows);
                $resdata=array($qrydata);
        
                echo json_encode($resdata);
                exit();
                
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action to load blood groups for dropdown in the popup ends

    }
?>