<?php
require_once __DIR__ . '/api_bootstrap.php';
require_admin();
include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to save paymet details start
        if($action=="save_payment"){ 
            $current_payment = $_POST['amount'];
            $expense_id = (int)($_POST['id'] ?? 0);
            try{
                $tm->begin();
                $group_id = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : 2;
                $flag = !empty($_POST['flag']) ? (int)$_POST['flag'] : 3;

                $bill_photo = null;
                if (!empty($_FILES['bill']['name']) && isset($_FILES['bill']['tmp_name']) && is_uploaded_file($_FILES['bill']['tmp_name'])) {
                    $upload_dir = '../../image_upload/bills/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $mimeType = mime_content_type($_FILES['bill']['tmp_name']);
                    $mimeToExt = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/gif'  => 'gif',
                        'image/webp' => 'webp',
                        'application/pdf' => 'pdf'
                    ];
                    if (isset($mimeToExt[$mimeType])) {
                        $ext = $mimeToExt[$mimeType];
                        $bill_photo = 'bill_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        move_uploaded_file($_FILES['bill']['tmp_name'], $upload_dir . $bill_photo);
                    }
                }

                if ($expense_id > 0) {
                    if ($bill_photo !== null) {
                        $sql = "UPDATE tbl_paid SET date = ?, head = ?, particuler = ?, amount = ?, transaction_type = ?, group_id = ?, bill_photo = ? WHERE id = ?";
                        $parameters = [$_POST['date'], $_POST['head'], $_POST['particuler'], $_POST['amount'], $_POST['transaction_type'], $group_id, $bill_photo, $expense_id];
                        $types = "sisiiisi";
                    } else {
                        $sql = "UPDATE tbl_paid SET date = ?, head = ?, particuler = ?, amount = ?, transaction_type = ?, group_id = ? WHERE id = ?";
                        $parameters = [$_POST['date'], $_POST['head'], $_POST['particuler'], $_POST['amount'], $_POST['transaction_type'], $group_id, $expense_id];
                        $types = "sisiiii";
                    }
                    app_exec_roll_back_nonquery($conn, $sql, $parameters, $types);
                } else {
                    $sql = "INSERT INTO tbl_paid (date, head, particuler, amount, payable_id, flag, transaction_type, group_id, bill_photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $parameters = [
                        $_POST['date'],
                        $_POST['head'],
                        $_POST['particuler'],
                        $_POST['amount'],
                        0,
                        $flag,
                        $_POST['transaction_type'],
                        $group_id,
                        ($bill_photo ?? '')
                    ];
                    $types = "sisiiiiis";
                    app_exec_roll_back_nonquery($conn, $sql, $parameters, $types);
                }

                $tm->commit();
                echo 'Updated Successfully';
            }
            catch (Exception $e) {
                $tm->rollback();
                echo $e->getMessage();
            }
        }

        if($action=="delete_payment"){
            try{
                $id = (int)$_POST['id'];
                if ($id > 0) {
                    $sql = "UPDATE tbl_paid SET cancel = 1 WHERE id = ?";
                    app_exec_getresult($sql, [$id], "i");
                    echo 'Deleted Successfully';
                }
                exit();
            } catch (Exception $e) {
                echo $e->getMessage();
                exit();
            }
        }
        // action to save payment details ends

        // action to load  heads for dropdown in the popup starts
        if($action=="load_heads"){
            try{
                $sql = "SELECT id, name FROM tbl_payment_head_master WHERE type = 'Debit'";
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
        // action to load heads for dropdown in the popup ends
        if($action=="load_closing_years"){
            try{
                $sql = "SELECT id, from_year, to_year FROM tbl_closing ORDER BY from_year DESC";
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

        // action to load all payable amount and paid amount details start
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;
                
                $sqldatarows ="SELECT p.id AS payable_id, p.date, p.particuler, p.amount AS payable_amount,
                p.head, p.iscomplete, g.type, g.name, SUM(pd.amount) AS total_paid_amount
                FROM tbl_payable AS p
                LEFT JOIN tbl_payment_head_master AS g ON p.head = g.id
                LEFT JOIN tbl_paid AS pd ON p.id = pd.payable_id
                GROUP BY p.id, p.date, p.particuler, p.amount, p.head, g.type, g.name
                ORDER BY p.date DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_payable ";
                
                $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
        
                $result = app_exec_query($sqldatarows);
                    
                $totalRowsResult = app_exec_query($sqlcountrows);
                $totalRows = $totalRowsResult->fetch_assoc()['total'];
                
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
           
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to load all payable amount and paid amount details end
    }
?>