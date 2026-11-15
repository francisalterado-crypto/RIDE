<form method="post" action="<?= base_url('projects/' . $project['id'] . '/innovation') ?>" class="card" style="background:#f9fafb;">
    <?= csrf_field() ?>
    <label>Record type</label>
    <select name="record_type" id="innovation-type" onchange="rideToggleInnovation(this.value)">
        <option value="ip_disclosures">IP Disclosure</option>
        <option value="patents">Patent</option>
        <option value="technology_transfers">Technology Transfer</option>
        <option value="prototypes">Prototype</option>
    </select>
    <div id="inn-title"><label>Title / Name</label><input name="title" id="inn-title-field"></div>
    <div id="inn-name" style="display:none;"><label>Name</label><input name="name"></div>
    <div id="inn-partner" style="display:none;"><label>Partner</label><input name="partner_name"></div>
    <label>Status / Stage</label>
    <input name="status" placeholder="Filed, granted, or beta">
    <button type="submit" class="btn btn-sm btn-accent">Add Record</button>
</form>
<script>
function rideToggleInnovation(t) {
    document.getElementById('inn-title').style.display = (t === 'prototypes') ? 'none' : 'block';
    document.getElementById('inn-name').style.display = (t === 'prototypes') ? 'block' : 'none';
    document.getElementById('inn-partner').style.display = (t === 'technology_transfers') ? 'block' : 'none';
}
</script>
