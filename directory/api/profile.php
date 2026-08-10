<?php
session_start();
include '../../app_common/db_connect.php';
include_once '../../app_common/auth_helper.php';
include '../../app_pagination/pagination.php';

    // action start
    if(isset($_POST['action']) && !empty($_POST['action'])) {

        $action = $_POST['action'];

        // action to save new profile or update new profile starts
        if($action=="save_profile"){
            try{
                $id = $_POST['id'] ?? $_SESSION['user_id'] ?? 0;
                // 1. Get the old email and existing image of this member before updating
                $old_member_sql = "SELECT email, img FROM tbl_members WHERE id = ?";
                $old_member_res = app_exec_getresult($old_member_sql, [$id], "i");
                $old_email = '';
                $old_img = '';
                if ($old_member_res && $row = $old_member_res->fetch_assoc()) {
                    $old_email = $row['email'];
                    $old_img = $row['img'];
                }

                $img_to_save = (!isset($_POST['img']) || $_POST['img'] === '') ? $old_img : $_POST['img'];

                $sql = "UPDATE tbl_members SET 
                            first_name = ?, 
                            middle_name = ?, 
                            last_name = ?, 
                            gender = ?, 
                            father_name = ?, 
                            mother_name = ?, 
                            dob = ?, 
                            blood_group = ?, 
                            phone = ?, 
                            whtsapp = ?, 
                            email = ?, 
                            p_street = ?, 
                            p_city = ?, 
                            p_pincode = ?, 
                            p_country = ?, 
                            img = ? 
                        WHERE id = ?";
                $parameters = [
                    $_POST['first_name'] ?? '',
                    $_POST['middle_name'] ?? '',
                    $_POST['last_name'] ?? '',
                    $_POST['gender'] ?? '',
                    $_POST['father_name'] ?? '',
                    $_POST['mother_name'] ?? '',
                    $_POST['dob'] ?? '',
                    $_POST['blood_group'] ?? '',
                    $_POST['phone'] ?? '',
                    $_POST['whtsapp'] ?? '',
                    $_POST['email'] ?? '',
                    $_POST['street'] ?? $_POST['p_street'] ?? '',
                    $_POST['city'] ?? $_POST['p_city'] ?? '',
                    $_POST['pincode'] ?? $_POST['p_pincode'] ?? '',
                    $_POST['country'] ?? $_POST['p_country'] ?? '',
                    $img_to_save,
                    $id
                ];
                $types = "sssssssssssssissi";
                app_exec_nonquery($sql, $parameters, $types);

                // Delete old image from server disk if a new image was uploaded
                if (!empty($old_img) && !empty($img_to_save) && $old_img !== $img_to_save) {
                    deleteOldMemberImage($old_img);
                }

                // 2. If the email was changed, update the login table to match
                $new_email = $_POST['email'] ?? '';
                if (!empty($old_email) && !empty($new_email) && $old_email !== $new_email) {
                    $login_id = $_SESSION['login_id'] ?? null;
                    if (!empty($login_id)) {
                        $update_login_sql = "UPDATE tbl_login SET email = ? WHERE login_id = ?";
                        app_exec_nonquery($update_login_sql, [$new_email, $login_id], "si");
                    } else {
                        $update_login_sql = "UPDATE tbl_login SET email = ? WHERE email = ?";
                        app_exec_nonquery($update_login_sql, [$new_email, $old_email], "ss");
                    }

                    // Also update active session if the edited member is the logged-in user
                    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
                        $_SESSION['email'] = $new_email;
                    }
                }

                echo "Saved Successfully";
                exit();
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }       
        }
        // action to save new profile or update new profile ends
             
        // action to load all profile starts
        if($action=="load_profile_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                $offset = ($current_page - 1) * $rowsPerPage;
                
                $user_id = $_SESSION['user_id'] ?? null;
                $login_id = $_SESSION['login_id'] ?? null;
                $email = $_SESSION['email'] ?? null;

                if (empty($user_id) && !empty($email)) {
                    $sql_heal = "SELECT id FROM tbl_members WHERE email = ?";
                    $res_heal = app_exec_getresult($sql_heal, [$email], "s");
                    if ($res_heal && $row_heal = $res_heal->fetch_assoc()) {
                        $user_id = $row_heal['id'];
                        $_SESSION['user_id'] = $user_id;
                    }
                }

                if (!empty($user_id)) {
                    $sqldatarows = "SELECT id, first_name, middle_name, last_name, gender, father_name, mother_name, dob, blood_group, phone, whtsapp, email, p_street AS street, p_city AS city, p_pincode AS pincode, p_country AS country, img FROM tbl_members WHERE id = ?";
                    $sqlcountrows = "SELECT COUNT(id) as total FROM tbl_members WHERE id = ?";
                    
                    $parameters = [$user_id];
                    $types = "i";
                    
                    $result = app_exec_getresult($sqldatarows, $parameters, $types);
                    $totalRowsResult = app_exec_getresult($sqlcountrows, $parameters, $types);
                    $totalRows = $totalRowsResult->fetch_assoc()['total'];
                } else if (!empty($login_id)) {
                    $sqldatarows = "SELECT login_id as id, name as first_name, '' as middle_name, '' as last_name, '' as gender, '' as father_name, '' as mother_name, '0000-00-00' as dob, 0 as blood_group, '' as phone, '' as whtsapp, email, '' as street, '' as city, 0 as pincode, '' as country, 'customer.png' as img FROM tbl_login WHERE login_id = ?";
                    $sqlcountrows = "SELECT COUNT(login_id) as total FROM tbl_login WHERE login_id = ?";
                    
                    $parameters = [$login_id];
                    $types = "i";
                    
                    $result = app_exec_getresult($sqldatarows, $parameters, $types);
                    $totalRowsResult = app_exec_getresult($sqlcountrows, $parameters, $types);
                    $totalRows = $totalRowsResult->fetch_assoc()['total'];
                } else {
                    $result = null;
                    $totalRows = 0;
                }

                //stringify
                $qrydata = [];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row;
                    }
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
        // action to load all profile ends

        // Action to load blood groups
        if($action=="load_blood_groups"){
            try{
                $sql = "SELECT id, name FROM tbl_bloodgroup_master ORDER BY name ASC";
                $result = app_exec_query($sql);
                $qrydata = [];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row;
                    }
                }
                echo json_encode([$qrydata]);
                exit();
            }
            catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['Message' => $e->getMessage()]);
                exit();
            }
        }

    }
?>