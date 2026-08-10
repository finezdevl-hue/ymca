<?php
class TransactionType {
    const CASH = 1;
    const BANK = 2;

    public static function all(): array {
        return [
            self::CASH => 'Cash',
            self::BANK => 'Bank',
        ];
    }
}

// to identify the payable is from members or not
class MemberTransaction {
    const member = 1;
    const other = 0;

    public static function all(): array {
        return [
            self::member => 'Member',
            self::other => 'Other',
        ];
    }
}

?>

