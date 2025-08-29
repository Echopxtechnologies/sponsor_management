<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">

            <div class="row m-b-10">
              <div class="col-md-8">
                <h4 class="customer-profile-group-heading">
                  <i class="fa fa-credit-card"></i> Sponsor Payments
                </h4>
              </div>
              <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('student_sponsor_portal/transactions'); ?>" class="btn btn-default">
                  <i class="fa fa-exchange"></i> Transactions
                </a>
              </div>
            </div>

            <!-- Filters (Date range + Search only) -->
            <form method="get" class="m-b-20">
              <div class="row">
                <div class="col-md-2">
                  <label>From</label>
                  <input type="date" name="date_from" class="form-control"
                         value="<?php echo html_escape($filters['date_from']); ?>">
                </div>
                <div class="col-md-2">
                  <label>To</label>
                  <input type="date" name="date_to" class="form-control"
                         value="<?php echo html_escape($filters['date_to']); ?>">
                </div>
                <div class="col-md-5">
                  <label>Search</label>
                  <input type="text" name="q" class="form-control"
                         placeholder="Search note, sponsor or student name..."
                         value="<?php echo html_escape($filters['q']); ?>">
                </div>
                <div class="col-md-3">
                  <label>&nbsp;</label><br>
                  <div class="text-right">
                    <button class="btn btn-primary">
                      <i class="fa fa-search"></i> Filter
                    </button>
                    <a href="<?php echo admin_url('student_sponsor_portal/payments'); ?>" class="btn btn-default">
                      Reset
                    </a>
                  </div>
                </div>
              </div>
            </form>

            <!-- Results -->
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Sponsor</th>
                    <th>Student</th>
                    <th>Type</th>
                    <th class="text-right">Amount</th>
                    <th>Currency</th>
                    <th>Note</th>
                    <th>Created By</th>
                    <th>Transaction</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($payments)): ?>
                    <tr><td colspan="9" class="text-center text-muted">No payments match your filters.</td></tr>
                  <?php else: foreach($payments as $p): ?>
                    <tr>
                      <td><?php echo _d($p->payment_date); ?></td>
                      <td><?php echo html_escape($p->sponsor_name); ?></td>
                      <td><?php echo html_escape($p->student_name ?: '-'); ?></td>
                      <td><?php echo ucfirst($p->student_type ?: '-'); ?></td>
                      <td class="text-right"><?php echo _format_number($p->amount); ?></td>
                      <td><?php echo html_escape($p->currency); ?></td>
                      <td><?php echo html_escape($p->note); ?></td>
                      <td>
                        <?php
                          if (!empty($p->created_by)) {
                            $u = $this->db->select('firstname,lastname')->where('staffid',(int)$p->created_by)->get(db_prefix().'staff')->row();
                            echo $u ? html_escape(trim($u->firstname.' '.$u->lastname)) : '-';
                          } else { echo '-'; }
                        ?>
                      </td>
                      <td>
                        <a class="btn btn-xs btn-default" href="<?php echo admin_url('student_sponsor_portal/transaction/'.(int)$p->transaction_id.'?tab=payments'); ?>">
                          <i class="fa fa-external-link"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <?php
              $pages = (int)ceil($total / $per_page);
              if ($pages < 1) $pages = 1;
              $qs = $_GET; unset($qs['page']);
            ?>
            <nav aria-label="Page navigation" class="text-right">
              <ul class="pagination">
                <?php for($i=1;$i<=$pages;$i++):
                  $qs['page'] = $i;
                  $url = admin_url('student_sponsor_portal/payments').'?'.http_build_query($qs);
                ?>
                  <li class="<?php echo ($i==$page)?'active':''; ?>">
                    <a href="<?php echo $url; ?>"><?php echo $i; ?></a>
                  </li>
                <?php endfor; ?>
              </ul>
            </nav>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
