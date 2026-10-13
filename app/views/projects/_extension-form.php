<form method="post" action="<?= base_url('projects/' . $project['id'] . '/extension') ?>" class="card" style="background:#f9fafb;">
    <?= csrf_field() ?>
    <label>Record type</label>
    <select name="record_type" onchange="rideToggleExtension(this.value)">
        <option value="community_beneficiaries">Community Beneficiaries</option>
        <option value="partner_mous">Partner MOU</option>
        <option value="impact_metrics">Impact Metrics</option>
    </select>
    <div id="ext-beneficiary">
        <label>Group name</label><input name="group_name" maxlength="255">
        <label>Beneficiary count</label><input type="number" name="beneficiary_count" min="0" value="0">
        <label>Location</label><input name="location">
        <label>Year</label><input type="number" name="beneficiary_year" min="1900" max="2100" value="<?= date('Y') ?>">
    </div>
    <div id="ext-mou" style="display:none;">
        <label>Partner name</label><input name="partner_name" maxlength="255">
        <label>Valid from</label><input type="date" name="valid_from">
        <label>Valid until</label><input type="date" name="valid_until">
    </div>
    <div id="ext-impact" style="display:none;">
        <label>Year</label><input type="number" name="impact_year" min="1900" max="2100" value="<?= date('Y') ?>">
        <label>People trained</label><input type="number" name="people_trained" min="0" value="0">
        <label>Income generated</label><input type="number" step="0.01" name="income_generated" value="0">
        <label>Households served</label><input type="number" name="households_served" min="0" value="0">
    </div>
    <button type="submit" class="btn btn-sm btn-accent">Add Record</button>
</form>
<script>
function rideToggleExtension(t) {
    document.getElementById('ext-beneficiary').style.display = t === 'community_beneficiaries' ? 'block' : 'none';
    document.getElementById('ext-mou').style.display = t === 'partner_mous' ? 'block' : 'none';
    document.getElementById('ext-impact').style.display = t === 'impact_metrics' ? 'block' : 'none';
}
</script>
