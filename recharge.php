<?php
/**
 * RISE - Wallet Recharge (Razorpay)
 * ====================================
 */

$pageTitle = 'Recharge Wallet';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
requireAdmin();

if (isSuperAdmin()) {
    setFlashMessage('error', 'Not available for super admin.');
    header('Location: dashboard.php');
    exit;
}

$currentUser = getCurrentUser();
$balance = $currentUser['wallet_balance'];
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Recharge Wallet</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <p class="text-muted">Current Balance</p>
                    <h2 class="text-primary"><?php echo CURRENCY_SYMBOL . number_format($balance, 2); ?></h2>
                </div>

                <form id="rechargeForm">
                    <div class="mb-3">
                        <label class="form-label">Amount (<?php echo CURRENCY_SYMBOL; ?>) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg text-center" id="rechargeAmount"
                               min="<?php echo MIN_RECHARGE_AMOUNT; ?>" step="1" required
                               placeholder="Min <?php echo CURRENCY_SYMBOL . MIN_RECHARGE_AMOUNT; ?>">
                        <small class="text-muted">Minimum: <?php echo CURRENCY_SYMBOL . number_format(MIN_RECHARGE_AMOUNT); ?></small>
                    </div>

                    <!-- Quick amount buttons -->
                    <div class="d-flex flex-wrap gap-2 mb-4 justify-content-center">
                        <button type="button" class="btn btn-outline-primary quick-amount" data-amount="500"><?php echo CURRENCY_SYMBOL; ?>500</button>
                        <button type="button" class="btn btn-outline-primary quick-amount" data-amount="1000"><?php echo CURRENCY_SYMBOL; ?>1,000</button>
                        <button type="button" class="btn btn-outline-primary quick-amount" data-amount="2000"><?php echo CURRENCY_SYMBOL; ?>2,000</button>
                        <button type="button" class="btn btn-outline-primary quick-amount" data-amount="5000"><?php echo CURRENCY_SYMBOL; ?>5,000</button>
                    </div>

                    <button type="button" id="payNowBtn" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-bolt me-2"></i>Pay with Razorpay
                    </button>
                    <!-- Payment Features -->
<div class="mt-4">

    <div class="border rounded p-3 bg-light">

        <div class="text-center mb-3">
            <strong class="text-success">
                <i class="fas fa-shield-alt me-1"></i> Secure Payment
            </strong>
        </div>

        <div class="row text-center">

            <div class="col-4">
                <i class="fas fa-lock text-primary fs-4"></i>
                <p class="small mt-1 mb-0">256-bit SSL</p>
            </div>

            <div class="col-4">
                <i class="fas fa-credit-card text-primary fs-4"></i>
                <p class="small mt-1 mb-0">All Cards</p>
            </div>

            <div class="col-4">
                <i class="fas fa-mobile-alt text-primary fs-4"></i>
                <p class="small mt-1 mb-0">UPI / Wallet</p>
            </div>

        </div>

        <hr>

        <div class="row text-center">

            <div class="col-4">
                <i class="fas fa-bolt text-success fs-4"></i>
                <p class="small mt-1 mb-0">Instant Credit</p>
            </div>

            <div class="col-4">
                <i class="fas fa-check-circle text-success fs-4"></i>
                <p class="small mt-1 mb-0">100% Secure</p>
            </div>

            <div class="col-4">
                <i class="fas fa-headset text-success fs-4"></i>
                <p class="small mt-1 mb-0">24x7 Support</p>
            </div>

        </div>

        <div class="text-center mt-3">
            <small class="text-muted">
                Powered by <strong>Razorpay Secure Payment Gateway</strong>
            </small>
        </div>

    </div>

</div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Razorpay Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick amount buttons
    document.querySelectorAll('.quick-amount').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('rechargeAmount').value = this.getAttribute('data-amount');
        });
    });

    // Pay button
    document.getElementById('payNowBtn').addEventListener('click', function() {
        const amount = parseInt(document.getElementById('rechargeAmount').value);

        if (!amount || amount < <?php echo MIN_RECHARGE_AMOUNT; ?>) {
            alert('Minimum recharge amount is <?php echo CURRENCY_SYMBOL . MIN_RECHARGE_AMOUNT; ?>');
            return;
        }

        const options = {
            key: '<?php echo RAZORPAY_KEY_ID; ?>',
            amount: amount * 100,
            currency: '<?php echo CURRENCY; ?>',
            name: '<?php echo APP_NAME; ?>',
            description: 'Wallet Recharge',
            handler: function(response) {
                // Send to server
                const formData = new FormData();
                formData.append('razorpay_payment_id', response.razorpay_payment_id);
                formData.append('amount', amount);
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

                fetch('razorpay_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Recharge successful! Amount: <?php echo CURRENCY_SYMBOL; ?>' + amount);
                        window.location.href = 'wallet.php';
                    } else {
                        alert('Recharge failed: ' + data.message);
                    }
                })
                .catch(err => {
                    alert('Error processing payment. Please contact support.');
                    console.error(err);
                });
            },
            prefill: {
                name: '<?php echo addslashes($currentUser['name']); ?>',
                email: '<?php echo addslashes($currentUser['email']); ?>'
            },
            theme: {
                color: '#4e73df'
            },
            modal: {
                ondismiss: function() {
                    console.log('Payment cancelled');
                }
            }
        };

        const rzp = new Razorpay(options);
        rzp.open();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>