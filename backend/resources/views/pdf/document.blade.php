<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>{{ $d['number'] }}</title>
<style>
@page { margin: 132px 46px 100px 46px; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111; margin: 0; line-height: 1.3; }
table { border-collapse: collapse; width: 100%; }
.bg { position: fixed; top: -132px; left: -46px; width: 794px; height: 1123px; z-index: -1; }
.title { text-align: right; font-size: 24px; font-weight: bold; margin: 0 0 4px; }
.void { text-align: right; color: #B42318; font-weight: bold; font-size: 12px; margin: 0 0 4px; }
table.head td.side { vertical-align: top; }
table.head td.left { width: 48%; vertical-align: top; padding-right: 14px; }
table.ib { border-collapse: separate; border-spacing: 0 2px; }
table.ib td.il { text-align: right; font-weight: bold; padding: 2px 6px 2px 0; width: 54%; }
table.ib td.iv { background-color: #FFF1D1; border: 1px solid #F2D79A; padding: 2px 6px; width: 46%; }
.cb { text-align: center; border: 1px solid #F2D79A; background-color: #FFF1D1; padding: 6px 8px; }
.cb b { font-size: 11.5px; }
table.pt2 { margin-bottom: 10px; font-size: 9.5px; }
table.pt2 td, table.pt2 th { border: 1px solid #D6DCE8; padding: 3px 6px; }
tr.bar td { background-color: #FCE3B0; text-align: center; font-weight: bold; border-color: #F2D79A; }
tr.h th { background-color: #C7D6EE; text-align: center; font-weight: bold; }
td.c { text-align: center; }
td.r { text-align: right; }
.pd { font-size: 8.5px; color: #6b7280; }
tr.ft td { background-color: #FFF1D1; font-weight: bold; color: #1B3F9A; border-color: #F2D79A; }
tr.ft td.c { font-weight: normal; color: #111; }
tr.last td { background-color: #FCE3B0; }
table.grand { background-color: #F8C58F; border: 1px solid #EBB27A; margin: 2px 0 8px; }
table.grand td { padding: 6px 8px; font-weight: bold; font-size: 12px; }
.wb { text-align: center; border: 1px solid #F2D79A; background-color: #FFF1D1; padding: 4px; margin-top: 4px; }
.bh { background-color: #FCE3B0; border: 1px solid #F2D79A; font-weight: bold; padding: 3px 7px; margin-bottom: 4px; }
.keep { page-break-inside: avoid; }
table.sig { margin-top: 14px; }
table.sig td.s { width: 250px; text-align: center; }
.sn { margin-top: 34px; font-weight: bold; }
</style>
</head>
<body>
@if ($d['letterhead'])
<img class="bg" src="{{ $d['letterhead'] }}" alt="">
@endif

<div class="title">{{ $d['title'] }}</div>
@if ($d['cancelled'])
<div class="void">DOCUMENT ANNULÉ</div>
@endif

<table class="head">
  <tr>
    <td class="left"></td>
    <td class="side">
      <table class="ib">
        <tr><td class="il">N° {{ ucfirst($d['noun']) }} :</td><td class="iv">{{ $d['number'] }}</td></tr>
        <tr><td class="il">Date :</td><td class="iv">{{ $d['date'] }}</td></tr>
        <tr><td class="il">Réf. Dossier / BL-LTA :</td><td class="iv">{{ $d['dossier_ref'] }}</td></tr>
      </table>
    </td>
  </tr>
  <tr>
    <td class="left">
      <div class="cb">
        <b>{{ $d['client']['name'] }}</b>
        @if ($d['client']['address'])
        <div>{{ $d['client']['address'] }}</div>
        @endif
        @if ($d['client']['ifu'] || $d['client']['rccm'])
        <div>{{ $d['client']['ifu'] ? 'IFU : '.$d['client']['ifu'] : '' }}{{ $d['client']['ifu'] && $d['client']['rccm'] ? ' | ' : '' }}{{ $d['client']['rccm'] ? 'RCCM : '.$d['client']['rccm'] : '' }}</div>
        @endif
      </div>
    </td>
    <td class="side">
      <table class="ib">
        <tr><td class="il">Devise :</td><td class="iv">{{ $d['meta']['currency'] }}</td></tr>
        <tr><td class="il">Taux de change (si Euro) :</td><td class="iv">{{ $d['meta']['rate'] }}</td></tr>
        <tr><td class="il">Valeur CAF :</td><td class="iv">{{ $d['meta']['caf'] }}</td></tr>
        <tr><td class="il">Nb Conteneurs (20'/40') :</td><td class="iv">{{ $d['meta']['containers'] }}</td></tr>
      </table>
    </td>
  </tr>
</table>
<div style="height: 8px;"></div>

@if (count($d['debours']))
<table class="pt2">
  <tr class="bar"><td colspan="6">{{ $d['debours_title'] }}</td></tr>
  <tr class="h"><th style="width: 7%;">Item</th><th>Description</th><th style="width: 9%;">Unité</th><th style="width: 10%;">Quantité</th><th style="width: 17%;">Prix Unit. (XOF)</th><th style="width: 19%;">Montant (XOF)</th></tr>
  @foreach ($d['debours'] as $i => $r)
  <tr><td class="c">{{ $i + 1 }}</td><td>{{ $r['label'] }}@if ($r['note'])<div class="pd">{{ $r['note'] }}</div>@endif</td><td class="c">{{ $r['unit'] }}</td><td class="c">{{ $r['qty'] }}</td><td class="r">{{ $r['price'] }}</td><td class="r">{{ $r['total'] }}</td></tr>
  @endforeach
  <tr class="ft"><td colspan="5" class="r">TOTAL DÉBOURS (A)</td><td class="r">{{ $d['debours_total'] }}</td></tr>
</table>
@endif

@if (count($d['prestations']))
<table class="pt2">
  <tr class="bar"><td colspan="6">{{ $d['prestations_title'] }}</td></tr>
  <tr class="h"><th style="width: 7%;">Item</th><th>Description</th><th style="width: 9%;">Unité</th><th style="width: 10%;">Quantité</th><th style="width: 17%;">Prix Unit. (XOF)</th><th style="width: 19%;">Montant (XOF)</th></tr>
  @foreach ($d['prestations'] as $i => $r)
  <tr><td class="c">{{ $i + 1 }}</td><td>{{ $r['label'] }}@if ($r['note'])<div class="pd">{{ $r['note'] }}</div>@endif</td><td class="c">{{ $r['unit'] }}</td><td class="c">{{ $r['qty'] }}</td><td class="r">{{ $r['price'] }}</td><td class="r">{{ $r['total'] }}</td></tr>
  @endforeach
  <tr class="ft"><td colspan="5" class="r">SOUS-TOTAL PRESTATIONS (HT)</td><td class="r">{{ $d['sub'] }}</td></tr>
  @if ($d['global_discount'])
  <tr class="ft"><td colspan="5" class="r">REMISE GLOBALE</td><td class="r">{{ $d['global_discount'] }}</td></tr>
  <tr class="ft"><td colspan="5" class="r">PRESTATIONS HT APRÈS REMISE</td><td class="r">{{ $d['ht_after'] }}</td></tr>
  @endif
  <tr class="ft"><td colspan="4" class="r">TVA :</td><td class="c">{{ $d['vat_rate'] }} %</td><td class="r">{{ $d['vat'] }}</td></tr>
  <tr class="ft last"><td colspan="5" class="r">{{ $d['prestations_total_label'] }}</td><td class="r">{{ $d['prestations_total'] }}</td></tr>
</table>
@endif

<div class="keep">
  <table class="grand"><tr><td>{{ $d['grand_label'] }}</td><td style="text-align: right;">{{ $d['grand'] }}</td></tr></table>
  <div><b>Arrêtée la présente {{ $d['noun'] }} à la somme de :</b></div>
  <div class="wb">{{ $d['words'] }}</div>
  <div style="height: 10px;"></div>
  <div class="bh">CONDITIONS DE RÈGLEMENT &amp; COORDONNÉES BANCAIRES</div>
  <div>Banque : [{{ $d['bank']['name'] }}] &nbsp;|&nbsp; IBAN/RIB : [{{ $d['bank']['rib'] }}] &nbsp;|&nbsp; SWIFT : [{{ $d['bank']['swift'] }}]</div>
  <table class="sig"><tr><td></td><td class="s"><div>{{ $d['sign']['title'] }}</div><div class="sn">{{ $d['sign']['name'] }}</div></td></tr></table>
</div>
</body>
</html>