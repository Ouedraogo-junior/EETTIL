<?php

return [
    // Liste des régimes douaniers proposés dans le suivi (deviendra paramétrable en administration).
    'regimes' => ['IM4', 'IM5', 'IM7', 'EX1', 'Transit'],

    // Valeurs par défaut des paramètres de la société (modifiables en administration à l'étape 7).
    'settings' => [
        'vat_rate' => 18,
        'series' => '000',
        'invoice_prefix' => 'FCT',
        'proforma_prefix' => 'PRO',
        'company_ifu' => '00253875E',
        'company_rccm' => 'BF-OUA-01-2024-B13-17299',
        'bank_name' => 'UBA BURKINA',
        'bank_rib' => 'BF 24 26022 01242- 421140008835 - 94',
        'bank_swift' => 'BIBUBFBF',
        'signatory_title' => 'LE RESPONSABLE',
        'signatory_name' => 'EMILE K KALIPE',
    ],
];