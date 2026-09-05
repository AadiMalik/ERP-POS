<?php

return [
    'title' => '期間結帳規則',
    'intro' => 'Choose which checks must pass before a period (automatic or manual) is allowed to close. If any enabled check finds pending items, the period is left open and the specific items are shown on the 会计 期间s screen.',
    'check_unposted_journals' => 'Block closing if there are unposted (pending) journal entries in the period',
    'check_purchase_returns' => 'Block closing if there are 采购 退货s pending approval',
    'check_leave_requests' => 'Block closing if there are Leave Requests pending approval',
    'check_employee_advances' => 'Block closing if there are Employee Advances pending approval',
    'check_employee_exits' => 'Block closing if there are Resignations/Terminations pending approval',
    'could_not_save' => 'Could not save closing rules',
];
