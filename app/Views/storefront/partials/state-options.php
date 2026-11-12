<?php
$selectedState = strtoupper((string) ($selected ?? ''));
$states = [
    'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
    'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'DC' => 'District of Columbia',
    'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois',
    'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana',
    'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota',
    'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
    'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
    'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon',
    'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina', 'SD' => 'South Dakota',
    'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia',
    'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
    'AB' => 'Alberta', 'BC' => 'British Columbia', 'MB' => 'Manitoba', 'NB' => 'New Brunswick',
    'NL' => 'Newfoundland and Labrador', 'NS' => 'Nova Scotia', 'NT' => 'Northwest Territories',
    'NU' => 'Nunavut', 'ON' => 'Ontario', 'PE' => 'Prince Edward Island', 'QC' => 'Quebec',
    'SK' => 'Saskatchewan', 'YT' => 'Yukon',
];
?>
<option value="">Select state / province</option>
<optgroup label="United States">
  <?php foreach ($states as $code => $label): ?>
    <?php if (strlen($code) === 2 && !in_array($code, ['AB','BC','MB','NB','NL','NS','NT','NU','ON','PE','QC','SK','YT'], true)): ?>
      <option value="<?= e($code) ?>"<?= $selectedState === $code ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endif; ?>
  <?php endforeach; ?>
</optgroup>
<optgroup label="Canada">
  <?php foreach (['AB','BC','MB','NB','NL','NS','NT','NU','ON','PE','QC','SK','YT'] as $code): ?>
    <option value="<?= e($code) ?>"<?= $selectedState === $code ? ' selected' : '' ?>><?= e($states[$code]) ?></option>
  <?php endforeach; ?>
</optgroup>
