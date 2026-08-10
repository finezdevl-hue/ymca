<?php
session_start();

if (empty($_SESSION['login_id'])) {
    // Try autologin via cookie
    if (!empty($_COOKIE['remember_user'])) {
        $parts = explode(':', $_COOKIE['remember_user']);
        if (count($parts) === 2) {
            $login_id = $parts[0];
            $signature = $parts[1];
            $expected_sig = hash_hmac('sha256', $login_id, 'ymca-secure-secret-key-9988');
            if (hash_equals($expected_sig, $signature)) {
                include_once '../app_common/database_class.php';
                $db = new Database();
                $conn = $db->getConnection();
                $stmt = $conn->prepare("SELECT l.login_id, l.name, m.id AS user_id, l.email FROM tbl_login as l LEFT JOIN tbl_members AS m ON l.email=m.email WHERE l.login_id = ?");
                $stmt->bind_param("i", $login_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $row = $result->fetch_assoc()) {
                    $_SESSION['name'] = $row['name'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['id'] = $row["login_id"];
                    $_SESSION['login_id'] = $row["login_id"];
                    $_SESSION['user_id'] = $row["user_id"];
                }
            }
        }
    }
}

if (!empty($_SESSION['login_id']) && empty($_SESSION['user_id']) && !empty($_SESSION['email'])) {
    include_once '../app_common/database_class.php';
    $check_db = new Database();
    $check_conn = $check_db->getConnection();
    $stmt_heal = $check_conn->prepare("SELECT id FROM tbl_members WHERE email = ?");
    if ($stmt_heal) {
        $stmt_heal->bind_param("s", $_SESSION['email']);
        $stmt_heal->execute();
        $res_heal = $stmt_heal->get_result();
        if ($res_heal && $row_heal = $res_heal->fetch_assoc()) {
            $_SESSION['user_id'] = $row_heal['id'];
        }
        $stmt_heal->close();
    }
}

if (empty($_SESSION['login_id'])) {
    echo "<script>window.location.href='../app_login_manager/logout.php';</script>";
    exit();
}
session_write_close();
include_once __DIR__ . '/../app_common/db_connect.php';


if(isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];

    if($action=="load_menu_data"){ 
        try{
            include_once __DIR__ . '/../app_common/auth_helper.php';
            $current_login = (int)$_SESSION['login_id'];

            // Auto-assign role-specific menu items
            if (!isSuperAdmin($current_login)) {
                $menus_to_ensure = [23, 29, 42, 43, 44, 45, 46]; // Default member menus

                if (isGroupAdmin($current_login)) {
                    // Group Admin gets Dashboard, Attendance, Income, All Reports, Fees, Payable, Cashbook, Members
                    $menus_to_ensure = array_merge($menus_to_ensure, [1, 2, 3, 4, 5, 6, 7, 12, 14, 15, 16, 17, 18, 19, 20, 21, 24, 36, 38, 39, 40, 41]);
                } else if (isAttendanceMaster($current_login)) {
                    // Attendance Master gets Attendance pages & reports
                    $menus_to_ensure = array_merge($menus_to_ensure, [12, 14, 28, 36, 37, 38]);
                } else if (isExecutiveMember($current_login)) {
                    // Executive Member gets all report pages & member pages
                    $menus_to_ensure = array_merge($menus_to_ensure, [15, 16, 17, 19, 20, 21, 36, 38, 39, 40]);
                }

                foreach (array_unique($menus_to_ensure) as $mid) {
                    $chk = app_exec_getresult("SELECT id FROM tbl_menu_map WHERE login_id = ? AND menu_id = ?", [$current_login, $mid], "ii");
                    if ($chk && $chk->num_rows == 0) {
                        app_exec_nonquery("INSERT INTO tbl_menu_map (login_id, menu_id, is_active) VALUES (?, ?, 1)", [$current_login, $mid], "ii");
                    }
                }
            }

            // Fetch all allowed menu items for the current user
            $sql ="SELECT qry1.menu_id as menu_id, qry1.menu_name as menu_name, qry1.nav_url as nav_url, qry1.menu_parent_id as 
            menu_parent_id, qry1.menu_level as menu_level, qry1.sub_menu as sub_menu, qry1.icon AS icon FROM (SELECT b.id as menu_id, b.name as menu_name, b.nav_url as 
            nav_url, b.parent_id as menu_parent_id, b.menu_level as menu_level, b.sub_menu as sub_menu, b.icon FROM tbl_menu b LEFT OUTER JOIN 
            tbl_menu as a on b.parent_id=a.id ) AS qry1 INNER JOIN (select menu_id from 
            tbl_menu_map where login_id='" . $_SESSION['login_id'] . "') as c on qry1.menu_id=c.menu_id";

            $result = app_exec_query($sql);
            $allowed_items = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    if ($row['nav_url'] == 'attendance1.php') {
                        $row['nav_url'] = 'attendance.php';
                    }
                    $allowed_items[$row['menu_id']] = $row;
                }
            }

            // If normal member, strictly restrict allowed items to member-only menu IDs
            if (isNormalMember($current_login)) {
                $member_allowed_menu_ids = [23, 29, 30, 42, 43, 44, 45, 46];
                foreach (array_keys($allowed_items) as $mid) {
                    if (!in_array($mid, $member_allowed_menu_ids)) {
                        unset($allowed_items[$mid]);
                    }
                }
            } else if (!isSuperAdmin($current_login)) {
                // Hide Super Admin menu settings (52) for non-super-admins
                unset($allowed_items[52]);
            }

            // Desired Menu Structure based on exact Role
            if (isNormalMember($current_login)) {
                $menu_structure = [
                    [
                        'name' => 'Dashboard',
                        'icon' => 'fa fa-th-large',
                        'possible_ids' => [42],
                        'forced_url' => 'member_dashboard.php'
                    ],
                    [
                        'name' => 'Attendance',
                        'icon' => 'fa fa-calendar',
                        'possible_ids' => [44, 25, 11],
                        'custom_children' => [
                            [
                                'menu_name' => 'Mark My Attendance',
                                'nav_url' => 'user_attendance.php',
                                'icon' => 'fa fa-calendar-check-o'
                            ],
                            [
                                'menu_name' => 'Monthly Attendance Report',
                                'nav_url' => 'monthly_attendance.php',
                                'icon' => 'fa fa-calendar-o'
                            ]
                        ]
                    ],
                    [
                        'name' => 'Your Wallet',
                        'icon' => 'fa fa-google-wallet',
                        'possible_ids' => [30],
                        'forced_url' => 'user_wallet.php'
                    ],
                    [
                        'name' => 'Your Cash Ledger',
                        'icon' => 'fa fa-user-circle',
                        'possible_ids' => [23],
                        'forced_url' => 'member_cashbook_report.php'
                    ],
                    [
                        'name' => 'My Profile',
                        'icon' => 'fa fa-user',
                        'possible_ids' => [43],
                        'custom_children' => [
                            [
                                'menu_name' => 'My Profile',
                                'nav_url' => 'profile.php',
                                'icon' => 'fa fa-user-circle'
                            ],
                            [
                                'menu_name' => 'Change Password',
                                'nav_url' => '../app_login_manager/forgot_password.php',
                                'icon' => 'fa fa-key'
                            ]
                        ]
                    ]
                ];
            } else if (isGroupAdmin($current_login) && !isSuperAdmin($current_login)) {
                $menu_structure = [
                    [
                        'name' => 'Group Dashboard',
                        'icon' => 'fa fa-th-large',
                        'possible_ids' => [41, 42],
                        'forced_url' => 'group_dashboard.php'
                    ],
                    [
                        'name' => 'Group Members',
                        'icon' => 'fa fa-users',
                        'possible_ids' => [1, 24]
                    ],
                    [
                        'name' => 'Attendance',
                        'icon' => 'fa fa-calendar',
                        'possible_ids' => [11, 25],
                        'children_ids' => [44, 12, 14, 36, 38]
                    ],
                    [
                        'name' => 'Income',
                        'icon' => 'fa fa-download',
                        'possible_ids' => [2],
                        'children_ids' => [3, 4, 5]
                    ],
                    [
                        'name' => 'Expenses',
                        'icon' => 'fa fa-upload',
                        'possible_ids' => [50],
                        'children_ids' => [6, 7]
                    ],
                    [
                        'name' => 'Reports',
                        'icon' => 'fa fa-bar-chart',
                        'possible_ids' => [15],
                        'children_ids' => [16, 17, 19, 20, 21, 36, 38, 39, 40],
                        'custom_children' => [
                            [
                                'menu_name' => 'Monthly Financial Report',
                                'nav_url' => 'monthly_financial_report.php',
                                'icon' => 'fa fa-line-chart'
                            ]
                        ]
                    ],
                    [
                        'name' => 'My Profile',
                        'icon' => 'fa fa-user',
                        'possible_ids' => [43],
                        'custom_children' => [
                            [
                                'menu_name' => 'My Profile',
                                'nav_url' => 'profile.php',
                                'icon' => 'fa fa-user-circle'
                            ],
                            [
                                'menu_name' => 'Change Password',
                                'nav_url' => '../app_login_manager/forgot_password.php',
                                'icon' => 'fa fa-key'
                            ]
                        ]
                    ]
                ];
            } else if (isAttendanceMaster($current_login) && !isSuperAdmin($current_login)) {
                $menu_structure = [
                    [
                        'name' => 'Dashboard',
                        'icon' => 'fa fa-th-large',
                        'possible_ids' => [42],
                        'forced_url' => 'member_dashboard.php'
                    ],
                    [
                        'name' => 'Attendance',
                        'icon' => 'fa fa-calendar',
                        'possible_ids' => [44, 25, 11],
                        'custom_children' => [
                            [
                                'menu_name' => 'Mark My Attendance',
                                'nav_url' => 'user_attendance.php',
                                'icon' => 'fa fa-calendar-check-o'
                            ],
                            [
                                'menu_name' => 'Mark All Attendance',
                                'nav_url' => 'attendance.php',
                                'icon' => 'fa fa-users'
                            ],
                            [
                                'menu_name' => 'Monthly Attendance Report',
                                'nav_url' => 'monthly_attendance.php',
                                'icon' => 'fa fa-calendar-o'
                            ]
                        ]
                    ],
                    [
                        'name' => 'Your Wallet',
                        'icon' => 'fa fa-google-wallet',
                        'possible_ids' => [30],
                        'forced_url' => 'user_wallet.php'
                    ],
                    [
                        'name' => 'Your Cash Ledger',
                        'icon' => 'fa fa-user-circle',
                        'possible_ids' => [23],
                        'forced_url' => 'member_cashbook_report.php'
                    ],
                    [
                        'name' => 'My Profile',
                        'icon' => 'fa fa-user',
                        'possible_ids' => [43],
                        'custom_children' => [
                            [
                                'menu_name' => 'My Profile',
                                'nav_url' => 'profile.php',
                                'icon' => 'fa fa-user-circle'
                            ],
                            [
                                'menu_name' => 'Change Password',
                                'nav_url' => '../app_login_manager/forgot_password.php',
                                'icon' => 'fa fa-key'
                            ]
                        ]
                    ]
                ];
            } else if (isExecutiveMember($current_login) && !isSuperAdmin($current_login) && !isGroupAdmin($current_login)) {
                $menu_structure = [
                    [
                        'name' => 'Dashboard',
                        'icon' => 'fa fa-th-large',
                        'possible_ids' => [42],
                        'forced_url' => 'member_dashboard.php'
                    ],
                    [
                        'name' => 'Attendance',
                        'icon' => 'fa fa-calendar',
                        'possible_ids' => [44, 25, 11],
                        'custom_children' => [
                            [
                                'menu_name' => 'Mark My Attendance',
                                'nav_url' => 'user_attendance.php',
                                'icon' => 'fa fa-calendar-check-o'
                            ],
                            [
                                'menu_name' => 'Monthly Attendance Report',
                                'nav_url' => 'monthly_attendance.php',
                                'icon' => 'fa fa-calendar-o'
                            ]
                        ]
                    ],
                    [
                        'name' => 'Reports',
                        'icon' => 'fa fa-bar-chart',
                        'possible_ids' => [15],
                        'custom_children' => [
                            [
                                'menu_name' => 'Monthly Financial Report',
                                'nav_url' => 'monthly_financial_report.php',
                                'icon' => 'fa fa-line-chart'
                            ],
                            [
                                'menu_name' => 'Fee Collection Report',
                                'nav_url' => 'fee_collection_report.php',
                                'icon' => 'fa fa-list-alt'
                            ],
                            [
                                'menu_name' => 'Payment History Report',
                                'nav_url' => 'payment_history_report.php',
                                'icon' => 'fa fa-history'
                            ],
                            [
                                'menu_name' => 'Financial Year Report',
                                'nav_url' => 'financial_year_report.php',
                                'icon' => 'fa fa-calendar'
                            ],
                            [
                                'menu_name' => 'Monthly Attendance Report',
                                'nav_url' => 'monthly_attendance.php',
                                'icon' => 'fa fa-check-square-o'
                            ],
                            [
                                'menu_name' => 'Yearly Attendance Report',
                                'nav_url' => 'yearly_attendance_report.php',
                                'icon' => 'fa fa-bar-chart'
                            ]
                        ]
                    ],
                    [
                        'name' => 'Your Wallet',
                        'icon' => 'fa fa-google-wallet',
                        'possible_ids' => [30],
                        'forced_url' => 'user_wallet.php'
                    ],
                    [
                        'name' => 'Your Cash Ledger',
                        'icon' => 'fa fa-user-circle',
                        'possible_ids' => [23],
                        'forced_url' => 'member_cashbook_report.php'
                    ],
                    [
                        'name' => 'My Profile',
                        'icon' => 'fa fa-user',
                        'possible_ids' => [43],
                        'custom_children' => [
                            [
                                'menu_name' => 'My Profile',
                                'nav_url' => 'profile.php',
                                'icon' => 'fa fa-user-circle'
                            ],
                            [
                                'menu_name' => 'Change Password',
                                'nav_url' => '../app_login_manager/forgot_password.php',
                                'icon' => 'fa fa-key'
                            ]
                        ]
                    ]
                ];
            } else {
                // Super Admin Menu Structure
                $menu_structure = [
                    // 1. Dashboard
                    [
                        'name' => 'Dashboard',
                        'icon' => 'fa fa-th-large',
                        'possible_ids' => [41, 42]
                    ],
                    // 2. Members
                    [
                        'name' => 'Members',
                        'icon' => 'fa fa-users',
                        'possible_ids' => [1, 24]
                    ],
                    // 2b. Guests List
                    [
                        'name' => 'Guests List',
                        'icon' => 'fa fa-user-circle',
                        'possible_ids' => [1, 24],
                        'forced_url' => 'guests.php'
                    ],
                    // 3. Attendance
                    [
                        'name' => 'Attendance',
                        'icon' => 'fa fa-calendar',
                        'possible_ids' => [11, 25],
                        'children_ids' => [44, 12, 52, 14, 13, 26, 27, 28, 36, 37, 38, 45, 46]
                    ],
                    // 4. Income
                    [
                        'name' => 'Income',
                        'icon' => 'fa fa-download',
                        'possible_ids' => [2],
                        'children_ids' => [3, 4, 5]
                    ],
                    // 5. Expense
                    [
                        'name' => 'Expense',
                        'icon' => 'fa fa-upload',
                        'possible_ids' => [50],
                        'children_ids' => [6, 7]
                    ],
                    // 6. Bank & FD
                    [
                        'name' => 'Bank & FD',
                        'icon' => 'fa fa-university',
                        'possible_ids' => [47],
                        'children_ids' => [48, 49]
                    ],
                    // 7. Wallet
                    [
                        'name' => 'Wallet',
                        'icon' => 'fa fa-google-wallet',
                        'possible_ids' => [8, 30]
                    ],
                    // 8. Reports
                    [
                        'name' => 'Reports',
                        'icon' => 'fa fa-bar-chart',
                        'possible_ids' => [15],
                        'children_ids' => [16, 17, 19, 20, 21, 36, 38, 39, 40],
                        'custom_children' => [
                            [
                                'menu_name' => 'Monthly Financial Report',
                                'nav_url' => 'monthly_financial_report.php',
                                'icon' => 'fa fa-line-chart',
                                'permission_id' => 15
                            ]
                        ]
                    ],
                    // 9. Menu Settings
                    [
                        'name' => 'Menu Settings',
                        'icon' => 'fa fa-cog',
                        'possible_ids' => [22]
                    ],
                    // 10. Account Settings
                    [
                        'name' => 'Account Settings',
                        'icon' => 'fa fa-gears',
                        'possible_ids' => [51],
                        'children_ids' => [9, 10, 31],
                        'custom_children' => [
                            [
                                'menu_name' => 'Manage Groups',
                                'nav_url' => 'groups.php',
                                'icon' => 'fa fa-object-group',
                                'permission_id' => 51
                            ],
                            [
                                'menu_name' => 'Role & Group Manager',
                                'nav_url' => 'role_management.php',
                                'icon' => 'fa fa-shield',
                                'permission_id' => 51
                            ],
                            [
                                'menu_name' => 'Payment & UPI Settings',
                                'nav_url' => 'payment_settings.php',
                                'icon' => 'fa fa-credit-card',
                                'permission_id' => 51
                            ],
                            [
                                'menu_name' => 'Database Backup',
                                'nav_url' => 'api/download_backup.php',
                                'icon' => 'fa fa-database',
                                'permission_id' => 51
                            ]
                        ]
                    ],
                    // 11. My Profile
                    [
                        'name' => 'My Profile',
                        'icon' => 'fa fa-user',
                        'possible_ids' => [43],
                        'custom_children' => [
                            [
                                'menu_name' => 'My Profile',
                                'nav_url' => 'profile.php',
                                'icon' => 'fa fa-user-circle'
                            ],
                            [
                                'menu_name' => 'Change Password',
                                'nav_url' => '../app_login_manager/forgot_password.php',
                                'icon' => 'fa fa-key',
                                'permission_id' => 29
                            ]
                        ]
                    ],
                    // 12. Your Cash Ledger
                    [
                        'name' => 'Your Cash Ledger',
                        'icon' => 'fa fa-user-circle',
                        'possible_ids' => [23]
                    ]
                ];
            }

            // Resolve avatar file
            $avatar_file = '../img/customer.png';
            if ($_SESSION['login_id'] != 1) {
                if (!empty($_SESSION['user_id'])) {
                    // Reconnect to get image
                    $db = new Database();
                    $conn = $db->getConnection();
                    $m_stmt = $conn->prepare("SELECT img FROM tbl_members WHERE id = ?");
                    if ($m_stmt) {
                        $m_stmt->bind_param("i", $_SESSION['user_id']);
                        $m_stmt->execute();
                        $m_res = $m_stmt->get_result()->fetch_assoc();
                        $m_stmt->close();
                        if ($m_res && $m_res['img'] && $m_res['img'] != '0') {
                            $avatar_file = '../image_upload/members/uploads/' . $m_res['img'];
                        }
                    }
                    $db->closeConnection();
                }
            }

            // Build HTML
            $stringval = "<ul class=\"nav metismenu\" id=\"side-menu\" data-avatar=\"$avatar_file\">";

            foreach ($menu_structure as $parent_item) {
                // Check if any of the possible_ids are allowed
                $allowed_parent_id = null;
                foreach ($parent_item['possible_ids'] as $pid) {
                    if (isset($allowed_items[$pid])) {
                        $allowed_parent_id = $pid;
                        break;
                    }
                }

                // If not allowed, check if parent_id is missing but children are allowed
                $allowed_children = [];
                $seen_children = [];
                if (isset($parent_item['children_ids'])) {
                    foreach ($parent_item['children_ids'] as $cid) {
                        if (isset($allowed_items[$cid])) {
                            $child = $allowed_items[$cid];
                            $unique_key = trim($child['menu_name']) . '|' . trim($child['nav_url']);
                            if (!in_array($unique_key, $seen_children)) {
                                $seen_children[] = $unique_key;
                                $allowed_children[] = $child;
                            }
                        }
                    }
                }

                // Check custom children
                $allowed_custom_children = [];
                if (isset($parent_item['custom_children'])) {
                    foreach ($parent_item['custom_children'] as $cc) {
                        if (isset($cc['permission_id'])) {
                            if (isset($allowed_items[$cc['permission_id']])) {
                                $allowed_custom_children[] = $cc;
                            }
                        } else {
                            $allowed_custom_children[] = $cc;
                        }
                    }
                }

                // If neither parent nor children are allowed, skip
                if ($allowed_parent_id === null && empty($allowed_children) && empty($allowed_custom_children)) {
                    continue;
                }

                $parent_name = $parent_item['name'];
                $parent_icon = $parent_item['icon'];

                // Decide rendering style
                if (empty($allowed_children) && empty($allowed_custom_children)) {
                    // Render as a single Level 1 link
                    $nav_url = isset($parent_item['forced_url']) ? $parent_item['forced_url'] : (isset($allowed_items[$allowed_parent_id]) ? $allowed_items[$allowed_parent_id]['nav_url'] : '#');
                    // Override nav url if missing
                    if (empty($nav_url) || $nav_url == '#') {
                        if (isSuperAdmin($current_login)) {
                            $nav_url = 'dashboard.php';
                        } else if (isGroupAdmin($current_login)) {
                            $nav_url = 'group_dashboard.php';
                        } else if ($allowed_parent_id == 41) {
                            $nav_url = 'dashboard.php';
                        } else if ($allowed_parent_id == 42) {
                            $nav_url = 'member_dashboard.php';
                        }
                        if ($allowed_parent_id == 8) $nav_url = 'wallet.php';
                        if ($allowed_parent_id == 30) $nav_url = 'user_wallet.php';
                        if ($allowed_parent_id == 43) $nav_url = 'profile.php';
                    }
                    if (isSuperAdmin($current_login) && $parent_name == 'Dashboard') {
                        $nav_url = 'dashboard.php';
                    } else if (isGroupAdmin($current_login) && $parent_name == 'Dashboard') {
                        $nav_url = 'group_dashboard.php';
                    }
                    $display_name = $parent_name;
                    if ($allowed_parent_id !== null && isset($allowed_items[$allowed_parent_id])) {
                        if ($allowed_parent_id == 44 || $allowed_parent_id == 23 || $allowed_parent_id == 30) {
                            $display_name = $allowed_items[$allowed_parent_id]['menu_name'];
                        }
                    }
                    $stringval .= "<li><a href=\"$nav_url\"><i class=\"$parent_icon\"></i> <span class=\"nav-label\">$display_name</span></a></li>";
                } else {
                    // Render as Level 1 parent with dropdown submenu children
                    $stringval .= "<li>";
                    $stringval .= "<a href=\"#\"><i class=\"$parent_icon\"></i> <span class=\"nav-label\">$parent_name</span><span class=\"fa arrow\"></span></a>";
                    $stringval .= "<ul class=\"nav nav-second-level collapse\">";

                    // If the parent itself has a valid nav_url (e.g. menu_id 44 = "Mark Attendance"),
                    // inject it as the first child link so it's clickable in the dropdown.
                    // Admin parents (menu_id 11, 25) have empty nav_urls so they are skipped.
                    // Skip menu_id 43 (My Profile) to avoid duplicating it since it is already added as a custom child.
                    if ($allowed_parent_id !== null && isset($allowed_items[$allowed_parent_id]) && empty($allowed_custom_children)) {
                        $parent_nav = trim($allowed_items[$allowed_parent_id]['nav_url']);
                        if (!empty($parent_nav) && $parent_nav !== '#' && $allowed_parent_id != 43) {
                            $p_icon = !empty($allowed_items[$allowed_parent_id]['icon']) ? $allowed_items[$allowed_parent_id]['icon'] : 'fa fa-angle-right';
                            $p_name = $allowed_items[$allowed_parent_id]['menu_name'];
                            $stringval .= "<li><a href=\"$parent_nav\"><i class=\"$p_icon\"></i> $p_name</a></li>";
                        }
                    }

                    // Output DB children
                    foreach ($allowed_children as $child) {
                        $child_url = $child['nav_url'];
                        $child_name = $child['menu_name'];
                        $child_icon = $child['icon'];
                        if (empty($child_icon)) {
                            $child_icon = 'fa fa-angle-right';
                        }
                        $stringval .= "<li><a href=\"$child_url\"><i class=\"$child_icon\"></i> $child_name</a></li>";
                    }

                    // Output custom children (e.g. My Profile / Change Password)
                    foreach ($allowed_custom_children as $cc) {
                        $cc_url = $cc['nav_url'];
                        $cc_name = $cc['menu_name'];
                        $cc_icon = $cc['icon'];
                        $stringval .= "<li><a href=\"$cc_url\"><i class=\"$cc_icon\"></i> $cc_name</a></li>";
                    }

                    $stringval .= "</ul>";
                    $stringval .= "</li>";
                }
            }

            $stringval .= "</ul>";
            echo $stringval;
        }
        catch (Throwable $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>
