<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content"><div class="row"><div class="col-md-12">
<div class="panel_s"><div class="panel-body">
  <div class="row m-b-10" >
    
    <div class="col-md-6" style="margin-top:40px;"><h4 class="customer-profile-group-heading"><i class="fa fa-exchange"></i> <?php echo html_escape($title); ?></h4></div>
    <div class="col-md-6 text-right">
      <a href="<?php echo admin_url('student_sponsor_portal/transaction'); ?>" class="btn btn-primary"><i class="fa fa-plus"></i> Add Transaction</a>
    </div>
  </div>
  <div class="table-responsive" style="margin-top:25px;">
    <table class="table table-striped dt-table">
      <thead><tr>
        <th>ID</th><th>Sponsor</th><th>Student</th><th>Total</th><th>Paid</th><th>Next Payment</th><th>Next Due</th><th>Type</th><th>Updated</th><th></th>
      </tr></thead>
      <tbody>
        <?php foreach ($txns as $t): ?>
        <tr>
          <td><?php echo (int)$t->id; ?></td>
          <td><?php $sp=$this->db->select('name')->where('id',$t->sponsor_id)->get('tblsponsor_records')->row(); echo $sp?html_escape($sp->name):'-'; ?></td>
          <td>
            <?php
              $label='-';
              if ($t->school_student_id) { $st=$this->db->select('name')->where('id',$t->school_student_id)->get('tblschool_students')->row(); $label=$st?html_escape($st->name):$label; }
              elseif ($t->university_student_id) { $ut=$this->db->select('name')->where('id',$t->university_student_id)->get('tbluniversity_students')->row(); $label=$ut?html_escape($ut->name):$label; }
              echo $label;
            ?>
          </td>
          <td><?php echo app_format_money($t->total_amount, $t->currency); ?></td>
          <td><?php echo app_format_money($t->amount_paid, $t->currency); ?></td>
          <td><?php echo app_format_money($t->balance_amount, $t->currency); ?></td>
          <td><?php echo _d($t->next_payment_due); ?></td>
          <td><?php echo ucfirst(str_replace('_',' ',$t->payment_type)); ?></td>
          <td><?php echo _dt($t->updated_at); ?></td>
          <td class="text-right">
            <a class="btn btn-default btn-sm" href="<?php echo admin_url('student_sponsor_portal/transaction/'.$t->id); ?>"><i class="fa fa-pencil"></i></a>
            <a class="btn btn-danger btn-sm" href="<?php echo admin_url('student_sponsor_portal/transaction_delete/'.$t->id); ?>" onclick="return confirm('Delete this transaction?');"><i class="fa fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div></div>
</div></div></div>
<?php init_tail(); ?>
