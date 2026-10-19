<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
requireAuthentication();
require_once __DIR__ . DIRECTORY_SEPARATOR . 'BookLoan.php';

$loan = new BookLoan();
$message = '';
$messageType = 'success';
$editRecord = null;

function loanInput(string $key, array $record = []): string
{
    return htmlspecialchars((string) ($record[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken(postString('csrf_token'))) {
        http_response_code(400);
        $message = 'Your session expired. Refresh the page and try again.';
        $messageType = 'error';
    } elseif (isset($_POST['add_loan']) || isset($_POST['update_loan'])) {
        $loan->reference_no = trim(postString('reference_no'));
        $loan->book_title = trim(postString('book_title'));
        $loan->book_isbn = trim(postString('book_isbn'));
        $loan->borrower_name = trim(postString('borrower_name'));
        $loan->borrower_id = trim(postString('borrower_id'));
        $loan->loan_date = postString('loan_date');
        $loan->due_date = postString('due_date');
        $returnDate = postString('return_date');
        $loan->return_date = $returnDate !== '' ? $returnDate : null;
        $loan->status = postString('status') ?: 'Borrowed';

        if (isset($_POST['add_loan'])) {
            $newId = $loan->addBookLoan((int) $_SESSION['user']['user_id']);
            $message = $newId === false ? 'Failed to create the loan record.' : "Loan record #{$newId} was added successfully.";
            $messageType = $newId === false ? 'error' : 'success';
        } else {
            $id = (int) postString('loan_id');
            $success = $loan->updateBookLoan($id, (int) $_SESSION['user']['user_id']);
            $message = $success ? "Loan record #{$id} was updated successfully." : "Failed to update loan record #{$id}.";
            $messageType = $success ? 'success' : 'error';
        }
    } elseif (isset($_POST['delete_loan'])) {
        $id = (int) postString('loan_id');
        $success = $loan->deleteBookLoan($id, (int) $_SESSION['user']['user_id']);
        $message = $success ? "Loan record #{$id} was deleted." : 'Failed to delete the loan record.';
        $messageType = $success ? 'success' : 'error';
    }
}

if (isset($_GET['edit_id'])) {
    $editRecord = $loan->getBookLoanById((int) $_GET['edit_id']);
}

$keyword = isset($_GET['keyword']) && is_string($_GET['keyword']) ? trim($_GET['keyword']) : '';
$loans = $keyword === '' ? $loan->getBookLoans() : $loan->searchBookLoans($keyword);
$counts = $loan->getCounts();
$recentTransactions = $loan->getRecentTransactions();
$sessionManager = new AppSession();
$recentSessions = $sessionManager->getRecent();
$formAction = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Loan Management</title>
    <style>
        :root {
            --navy: #0f172a;
            --blue: #2563eb;
            --background: #f8fafc;
            --panel: #ffffff;
            --border: #dbe3ee;
            --muted: #64748b;
            --red: #b91c1c;
            --green: #166534;
            --amber: #a16207;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--background); color: #1e293b; font-family: Arial, sans-serif; line-height: 1.5; }
        header { background: var(--navy); color: #fff; padding: 22px 20px; }
        header .inner, main { max-width: 1200px; margin: 0 auto; }
        header h1 { margin: 0; font-size: 1.7rem; }
        header p { margin: 4px 0 0; color: #cbd5e1; }
        .account { display: flex; align-items: center; gap: 12px; margin-top: 12px; color: #e2e8f0; }
        .account form { margin: 0; }
        .account button { padding: 7px 12px; background: #334155; }
        main { padding: 28px 20px 48px; }
        .metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 22px; }
        .metric, .panel { background: var(--panel); border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 2px 6px rgba(15, 23, 42, .05); }
        .metric { padding: 17px; }
        .metric small { color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .metric strong { display: block; margin-top: 4px; font-size: 1.8rem; }
        .panel { padding: 22px; margin-bottom: 22px; }
        .panel h2 { margin: 0 0 18px; font-size: 1.15rem; }
        .notice { padding: 12px 14px; margin-bottom: 18px; border-radius: 6px; font-weight: 700; }
        .notice.success { background: #dcfce7; border: 1px solid #bbf7d0; color: var(--green); }
        .notice.error { background: #fee2e2; border: 1px solid #fecaca; color: var(--red); }
        form.grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        label { display: grid; gap: 6px; font-weight: 700; font-size: .85rem; }
        input, select { width: 100%; padding: 10px 11px; border: 1px solid var(--border); border-radius: 6px; font: inherit; background: #fff; }
        .wide { grid-column: 1 / -1; }
        button, .button { display: inline-block; padding: 10px 16px; border: 0; border-radius: 6px; background: var(--blue); color: #fff; font-weight: 700; text-decoration: none; cursor: pointer; }
        .button.muted { background: #e2e8f0; color: #1e293b; margin-left: 8px; }
        .search { display: flex; gap: 10px; }
        .search input { flex: 1; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        th { padding: 11px; background: #1e293b; color: #fff; text-align: left; white-space: nowrap; }
        td { padding: 11px; border-bottom: 1px solid var(--border); white-space: nowrap; }
        .status { display: inline-block; padding: 3px 8px; border-radius: 999px; font-weight: 700; font-size: .76rem; }
        .status.Borrowed { background: #fef3c7; color: var(--amber); }
        .status.Returned { background: #dcfce7; color: var(--green); }
        .status.Overdue { background: #fee2e2; color: var(--red); }
        .actions a { color: var(--blue); font-weight: 700; margin-right: 10px; text-decoration: none; }
        .actions a.delete { color: var(--red); }
        .actions form { display: inline; }
        .actions button.delete { padding: 0; background: transparent; color: var(--red); }
        .transaction-action { display: inline-block; padding: 3px 9px; border-radius: 999px; background: #e2e8f0; color: #334155; font-size: .76rem; font-weight: 700; text-transform: capitalize; }
        .transaction-action.created { background: #dcfce7; color: var(--green); }
        .transaction-action.updated { background: #dbeafe; color: #1d4ed8; }
        .transaction-action.deleted { background: #fee2e2; color: var(--red); }
        .session-status { display: inline-block; padding: 3px 9px; border-radius: 999px; background: #e2e8f0; color: #334155; font-size: .76rem; font-weight: 700; }
        .session-status.active { background: #dcfce7; color: var(--green); }
        .dashboard-nav { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 14px; }
        .dashboard-nav a { padding: 6px 10px; border: 1px solid #334155; border-radius: 6px; color: #e2e8f0; font-size: .82rem; text-decoration: none; }
        .dashboard-nav a:hover { background: #1e293b; }
        .empty-state { padding: 22px; color: var(--muted); text-align: center; }
        @media (max-width: 800px) { .metrics { grid-template-columns: repeat(2, 1fr); } form.grid { grid-template-columns: 1fr; } .wide { grid-column: auto; } }
        @media (max-width: 480px) { .metrics { grid-template-columns: 1fr; } .search { display: grid; } }
    </style>
</head>
<body>
<header><div class="inner"><h1>Library Loan Management</h1><p>Book loan and return tracking system</p><div class="account">Logged in as <?= htmlspecialchars($_SESSION['user']['full_name'], ENT_QUOTES, 'UTF-8') ?><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(createCsrfToken(), ENT_QUOTES, 'UTF-8') ?>"><button type="submit">Log Out</button></form></div><nav class="dashboard-nav" aria-label="Dashboard sections"><a href="#transactions">Transactions</a><a href="#sessions">Sessions</a></nav></div></header>
<main>
    <section class="metrics">
        <div class="metric"><small>Total loans</small><strong><?= $counts['total'] ?></strong></div>
        <div class="metric"><small>Borrowed</small><strong style="color: var(--amber)"><?= $counts['borrowed'] ?></strong></div>
        <div class="metric"><small>Overdue</small><strong style="color: var(--red)"><?= $counts['overdue'] ?></strong></div>
        <div class="metric"><small>Returned</small><strong style="color: var(--green)"><?= $counts['returned'] ?></strong></div>
    </section>

    <?php if ($message !== ''): ?><div class="notice <?= $messageType ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <section class="panel" id="transactions">
        <h2>Recent Transactions</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>Action</th><th>Reference</th><th>Details</th><th>Staff</th><th>Date &amp; Time</th></tr></thead>
            <tbody>
            <?php foreach ($recentTransactions as $transaction): ?>
                <tr>
                    <td><span class="transaction-action <?= htmlspecialchars($transaction['action'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($transaction['action'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($transaction['reference_no'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($transaction['summary'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($transaction['user_name'] ?: $transaction['username'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($transaction['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recentTransactions): ?><tr><td colspan="5" class="empty-state">No transactions yet. New loan creation, updates, and deletions will appear here.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>

    <section class="panel" id="sessions">
        <h2>Recent Sessions</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>Status</th><th>User</th><th>Login Time</th><th>Last Activity</th><th>Expires</th><th>Logged Out</th></tr></thead>
            <tbody>
            <?php foreach ($recentSessions as $session): ?>
                <?php $sessionIsActive = $session['ended_at'] === null && strtotime($session['expires_at']) > time(); ?>
                <tr>
                    <td><span class="session-status <?= $sessionIsActive ? 'active' : '' ?>"><?= $sessionIsActive ? 'Active' : ($session['ended_at'] !== null ? 'Logged out' : 'Expired') ?></span></td>
                    <td><?= htmlspecialchars($session['user_name'] ?: $session['username'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($session['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($session['last_activity'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($session['expires_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $session['ended_at'] !== null ? htmlspecialchars(date('M j, Y g:i A', strtotime($session['ended_at'])), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recentSessions): ?><tr><td colspan="6" class="empty-state">No login sessions have been recorded yet.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>

    <section class="panel">
        <h2><?= $editRecord ? 'Edit Loan Record #' . loanInput('loan_id', $editRecord) : 'Issue New Book Loan' ?></h2>
        <form class="grid" method="post" action="<?= $formAction ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(createCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($editRecord): ?><input type="hidden" name="loan_id" value="<?= loanInput('loan_id', $editRecord) ?>"><?php endif; ?>
            <label>Reference No.<input name="reference_no" required value="<?= loanInput('reference_no', $editRecord ?? []) ?>" placeholder="REF-10928"></label>
            <label>Book Title<input name="book_title" required value="<?= loanInput('book_title', $editRecord ?? []) ?>" placeholder="Book title"></label>
            <label>Book ISBN<input name="book_isbn" required value="<?= loanInput('book_isbn', $editRecord ?? []) ?>" placeholder="ISBN"></label>
            <label>Borrower Name<input name="borrower_name" required value="<?= loanInput('borrower_name', $editRecord ?? []) ?>" placeholder="Full name"></label>
            <label>Borrower ID<input name="borrower_id" required value="<?= loanInput('borrower_id', $editRecord ?? []) ?>" placeholder="Student number"></label>
            <label>Status<select name="status"><?php foreach (['Borrowed', 'Returned', 'Overdue'] as $status): ?><option value="<?= $status ?>" <?= (($editRecord['status'] ?? 'Borrowed') === $status) ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></label>
            <label>Loan Date<input type="date" name="loan_date" required value="<?= loanInput('loan_date', $editRecord ?? ['loan_date' => date('Y-m-d')]) ?>"></label>
            <label>Due Date<input type="date" name="due_date" required value="<?= loanInput('due_date', $editRecord ?? []) ?>"></label>
            <label>Return Date<input type="date" name="return_date" value="<?= loanInput('return_date', $editRecord ?? []) ?>"></label>
            <div class="wide">
                <?php if ($editRecord): ?><button type="submit" name="update_loan">Save Changes</button><a class="button muted" href="<?= $formAction ?>">Cancel</a><?php else: ?><button type="submit" name="add_loan">Create Loan Record</button><?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Search Loan Records</h2>
        <form class="search" method="get" action="<?= $formAction ?>">
            <input name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="Search reference, title, borrower, ID, ISBN, or status">
            <button type="submit">Search</button>
            <?php if ($keyword !== ''): ?><a class="button muted" href="<?= $formAction ?>">Clear</a><?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <h2><?= $keyword !== '' ? 'Results for &quot;' . htmlspecialchars($keyword) . '&quot;' : 'Loan Registry' ?> (<?= count($loans) ?>)</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>Reference</th><th>Book</th><th>ISBN</th><th>Borrower</th><th>Borrower ID</th><th>Loan Date</th><th>Due Date</th><th>Return Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($loans as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['reference_no']) ?></td><td><?= htmlspecialchars($row['book_title']) ?></td><td><?= htmlspecialchars($row['book_isbn']) ?></td><td><?= htmlspecialchars($row['borrower_name']) ?></td><td><?= htmlspecialchars($row['borrower_id']) ?></td><td><?= htmlspecialchars($row['loan_date']) ?></td><td><?= htmlspecialchars($row['due_date']) ?></td><td><?= htmlspecialchars($row['return_date'] ?? '') ?></td><td><span class="status <?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td class="actions"><a href="<?= $formAction ?>?edit_id=<?= (int) $row['loan_id'] ?>">Edit</a><form method="post" action="<?= $formAction ?>" onsubmit="return confirm('Delete this loan record?');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(createCsrfToken(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="loan_id" value="<?= (int) $row['loan_id'] ?>"><button class="delete" type="submit" name="delete_loan">Delete</button></form></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$loans): ?><tr><td colspan="10" style="text-align:center;color:var(--muted);padding:24px">No loan records found.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>
</main>
</body>
</html>
