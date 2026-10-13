<?php
require_once 'header.php';
?>

<div class="page-title">
    <h1><i class="fas fa-home"></i> මුල් පිටුව</h1>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
    <!-- Quick Actions -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 15px; color: var(--primary-dark);">
            <i class="fas fa-bolt"></i> ඉක්මන් ක්‍රියා
        </h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <a href="index.php" style="display: flex; align-items: center; gap: 10px; padding: 12px; background: #dbeafe; color: var(--primary-dark); text-decoration: none; border-radius: 5px;">
                <i class="fas fa-plus-circle"></i>
                <span>නව ගිවිසුමක් එක් කරන්න</span>
            </a>
            <a href="view_agreements.php" style="display: flex; align-items: center; gap: 10px; padding: 12px; background: #dcfce7; color: #059669; text-decoration: none; border-radius: 5px;">
                <i class="fas fa-list"></i>
                <span>සියලුම ගිවිසුම් බලන්න</span>
            </a>
            <a href="reports.php" style="display: flex; align-items: center; gap: 10px; padding: 12px; background: #fef3c7; color: #d97706; text-decoration: none; border-radius: 5px;">
                <i class="fas fa-chart-bar"></i>
                <span>වාර්තා බලන්න</span>
            </a>
        </div>
    </div>

    <!-- Recent Agreements -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 15px; color: var(--primary-dark);">
            <i class="fas fa-history"></i> මෑත ගිවිසුම්
        </h3>
        <div style="max-height: 300px; overflow-y: auto;">
            <?php
            require_once 'db.php';
            $recent_sql = "SELECT * FROM agreements ORDER BY created_at DESC LIMIT 5";
            $recent_result = $conn->query($recent_sql);
            
            if ($recent_result->num_rows > 0):
                while ($row = $recent_result->fetch_assoc()):
            ?>
                <div style="padding: 10px; border-bottom: 1px solid #e5e7eb;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="font-weight: 500;"><?php echo htmlspecialchars($row['name']); ?></span>
                        <span style="color: #059669; font-weight: 600;">රු. <?php echo number_format($row['amount_number'], 2); ?></span>
                    </div>
                    <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                        <?php echo date('Y-m-d', strtotime($row['agreement_date'])); ?>
                        <a href="preview.php?id=<?php echo $row['id']; ?>" style="margin-left: 10px; color: var(--primary); text-decoration: none;">
                            <i class="fas fa-eye"></i> බලන්න
                        </a>
                    </div>
                </div>
            <?php
                endwhile;
            else:
            ?>
                <div style="text-align: center; padding: 20px; color: #6b7280;">
                    <i class="fas fa-inbox" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
                    තවමත් ගිවිසුම් නැත
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>