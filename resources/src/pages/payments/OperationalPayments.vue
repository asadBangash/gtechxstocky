<template>
  <div class="page">
    <PageHeader :title="$t(cfg.titleKey)" :breadcrumb="[$t(cfg.parentKey), $t(cfg.titleKey)]">
      <template #actions>
        <a-space wrap>
          <a-button :loading="exporting === 'pdf'" @click="exportList('pdf')">
            <template #icon><FilePdfOutlined /></template>
            PDF
          </a-button>
          <a-button :loading="exporting === 'xlsx'" @click="exportList('xlsx')">
            <template #icon><FileExcelOutlined /></template>
            EXCEL
          </a-button>
          <a-button v-if="auth.can(cfg.addPermission)" type="primary" @click="openDocPicker">
            <template #icon><PlusOutlined /></template>
            {{ $t(cfg.titleKey) }}
          </a-button>
        </a-space>
      </template>
    </PageHeader>

    <a-row :gutter="[16, 16]" style="margin-bottom: 16px">
      <a-col v-for="tile in statTiles" :key="tile.label" :xs="12" :sm="12" :md="6">
        <a-card size="small" class="stat-card">
          <div class="stat-inner">
            <div class="stat-icon" :style="{ background: tile.tint, color: tile.color }">
              <component :is="tile.icon" />
            </div>
            <div class="stat-meta">
              <div class="stat-label">{{ tile.label }}</div>
              <div class="stat-value">
                <a-spin v-if="crud.loading.value" size="small" />
                <template v-else>{{ tile.value }}</template>
              </div>
            </div>
          </div>
        </a-card>
      </a-col>
    </a-row>

    <a-card size="small" style="margin-bottom: 16px">
      <a-row :gutter="[16, 8]" align="middle">
        <a-col :xs="24" :md="10" :xl="8">
          <DateRangePicker v-model:value="range" :allow-clear="false" @change="crud.reload()" />
        </a-col>
        <a-col :xs="12" :md="6" :xl="4">
          <a-input
            v-model:value="refFilter" allow-clear :placeholder="$t('Reference')"
            @press-enter="crud.reload()" @change="e => { if (!e.target.value) crud.reload(); }"
          />
        </a-col>
        <a-col :xs="12" :md="6" :xl="4">
          <a-select
            v-model:value="partyId" allow-clear show-search option-filter-prop="label" style="width: 100%"
            :placeholder="$t(cfg.partyLabel)" :options="partyOptions" @change="crud.reload()"
          />
        </a-col>
        <a-col :xs="12" :md="6" :xl="4">
          <a-select
            v-model:value="docId" allow-clear show-search option-filter-prop="label" style="width: 100%"
            :placeholder="$t(cfg.docRefLabel)" :options="docOptions" @change="crud.reload()"
          />
        </a-col>
        <a-col :xs="12" :md="6" :xl="4">
          <a-select
            v-model:value="methodId" allow-clear show-search option-filter-prop="label" style="width: 100%"
            :placeholder="$t('ModePaiement')" :options="methodOptions" @change="crud.reload()"
          />
        </a-col>
      </a-row>
    </a-card>

    <DataTable :crud="crud" :columns="columns">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'date'">{{ date(record.date) }}</template>
        <template v-else-if="column.key === 'doc'">
          <a @click="$router.push(`${cfg.docPath}/${record[cfg.docIdField]}`)">{{ record[cfg.docRefField] }}</a>
        </template>
        <template v-else-if="column.key === 'payment_method'">
          <a-tag>{{ record.payment_method }}</a-tag>
        </template>
        <template v-else-if="column.key === 'montant'">
          <strong>{{ money(record.montant) }}</strong>
        </template>
        <template v-else-if="column.key === 'actions'">
          <a-dropdown :trigger="['click']">
            <a-button type="text" size="small">
              <template #icon><MoreOutlined style="font-size: 18px" /></template>
            </a-button>
            <template #overlay>
              <a-menu @click="({ key }) => onAction(key, record)">
                <a-menu-item key="pdf">
                  <FilePdfOutlined /> {{ $t('DownloadPdf') }}
                </a-menu-item>
                <a-menu-item v-if="auth.can(cfg.editPermission)" key="edit">
                  <EditOutlined /> {{ $t('EditPayment') }}
                </a-menu-item>
                <a-menu-item v-if="auth.can(cfg.deletePermission)" key="delete" danger>
                  <DeleteOutlined /> {{ $t('Del') }}
                </a-menu-item>
              </a-menu>
            </template>
          </a-dropdown>
        </template>
      </template>
    </DataTable>

    <a-modal
      v-model:open="pickDocOpen"
      :title="$t(cfg.titleKey)"
      :ok-text="$t('submit')"
      :confirm-loading="pickDocLoading"
      @ok="confirmDocPick"
    >
      <a-form-item :label="$t(cfg.docRefLabel)" required>
        <a-select
          v-model:value="pickDocId"
          show-search allow-clear option-filter-prop="label"
          :placeholder="$t(cfg.docRefLabel)"
          :options="eligibleDocOptions"
          :loading="pickDocLoading"
          style="width: 100%"
        />
      </a-form-item>
    </a-modal>

    <a-modal
      v-model:open="payFormOpen"
      :title="`${payEditing ? $t('EditPayment') : $t(cfg.titleKey)} — ${activeDoc?.Ref || ''}`"
      :confirm-loading="paySaving"
      :ok-text="$t('submit')"
      @ok="submitPayment"
    >
      <a-form layout="vertical">
        <a-alert type="info" show-icon style="margin-bottom: 12px" :message="$t('Payment_Amounts_Help')" />
        <a-row :gutter="12">
          <a-col :span="12">
            <a-form-item :label="$t('date')">
              <a-date-picker v-model:value="payForm.date" value-format="YYYY-MM-DD" style="width: 100%" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item :label="$t('Amount_Paid')">
              <a-input-number v-model:value="payForm.montant" style="width: 100%" :min="0" :max="payMaxDue" />
              <a-typography-text type="secondary" style="font-size: 12px; display: block">
                {{ $t('Maximum_payment') }}: {{ money(payMaxDue) }}
              </a-typography-text>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item :label="$t('Received_Amount')">
              <a-input-number v-model:value="payForm.received_amount" style="width: 100%" :min="0" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item :label="$t('Paymentchoice')">
              <a-select v-model:value="payForm.payment_method_id" :options="methodOptions" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item :label="$t('Account')">
              <a-select v-model:value="payForm.account_id" allow-clear :placeholder="$t('Choose_Account')" :options="accountOptions" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item :label="$t('Change')">
              <a-input :value="money(payChange)" disabled />
            </a-form-item>
          </a-col>
          <a-col :span="24">
            <a-form-item :label="$t('Note')">
              <a-textarea v-model:value="payForm.notes" :rows="2" />
            </a-form-item>
          </a-col>
        </a-row>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup>
/**
 * Operational payment lists under Sales (Payment In) and Purchases (Payment Out).
 * Uses payment_sale / payment_purchase index payloads (same as payment reports).
 */
import { ref, computed, createVNode, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { message, Modal } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs';
import {
  PlusOutlined, EditOutlined, DeleteOutlined, MoreOutlined,
  FilePdfOutlined, FileExcelOutlined, WalletOutlined, DollarOutlined,
  ExclamationCircleOutlined,
} from '@ant-design/icons-vue';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import DateRangePicker from '../../components/DateRangePicker.vue';
import { useCrudTable } from '../../composables/useCrudTable';
import { useFormat } from '../../composables/useFormat';
import { useAuthStore } from '../../stores/auth';
import { exportExcel, exportPdf } from '../../lib/exporters';
import http from '../../lib/http';

const KINDS = {
  sale: {
    titleKey: 'Payment_In',
    parentKey: 'Sales',
    endpoint: 'payment_sale',
    listEndpoint: 'sales',
    listRowsKey: 'sales',
    docPath: '/sales',
    docIdField: 'sale_id',
    docRefField: 'Ref_Sale',
    docRefLabel: 'Choose_Sale_Ref',
    partyLabel: 'Customer',
    partyParam: 'client_id',
    partyListKey: 'clients',
    docParam: 'sale_id',
    addPermission: 'payment_sales_add',
    editPermission: 'payment_sales_edit',
    deletePermission: 'payment_sales_delete',
    pdfPrefix: 'payment_sale_pdf',
    payNotifyEmail: 'payment_sale_send_email',
    payNotifySms: 'payment_sale_send_sms',
    canPay: r => r.statut === 'completed' && Number(r.due) > 0,
    docDetailKey: 'sale',
  },
  purchase: {
    titleKey: 'Payment_Out',
    parentKey: 'Purchases',
    endpoint: 'payment_purchase',
    listEndpoint: 'purchases',
    listRowsKey: 'purchases',
    docPath: '/purchases',
    docIdField: 'purchase_id',
    docRefField: 'Ref_Purchase',
    docRefLabel: 'Choose_Purchase_Ref',
    partyLabel: 'Supplier',
    partyParam: 'provider_id',
    partyListKey: 'suppliers',
    docParam: 'purchase_id',
    addPermission: 'payment_purchases_add',
    editPermission: 'payment_purchases_edit',
    deletePermission: 'payment_purchases_delete',
    pdfPrefix: 'payment_purchase_pdf',
    payNotifyEmail: 'payment_purchase_send_email',
    payNotifySms: 'payment_purchase_send_sms',
    canPay: r => r.statut === 'received' && Number(r.due) > 0,
    docDetailKey: 'purchase',
  },
};

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const { money, date, roundMoney } = useFormat();
const auth = useAuthStore();

const paymentKind = route.meta.paymentKind || 'sale';
const cfg = KINDS[paymentKind] || KINDS.sale;

const range = ref([dayjs().subtract(29, 'day'), dayjs()]);
const refFilter = ref('');
const partyId = ref(undefined);
const docId = ref(undefined);
const methodId = ref(undefined);

const filterParams = () => ({
  from: range.value?.[0]?.format?.('YYYY-MM-DD') || '',
  to: range.value?.[1]?.format?.('YYYY-MM-DD') || '',
  Ref: refFilter.value || '',
  [cfg.partyParam]: partyId.value || '',
  [cfg.docParam]: docId.value || '',
  payment_method_id: methodId.value || '',
});

const crud = useCrudTable(cfg.endpoint, {
  rowsKey: 'payments',
  sortField: 'id',
  sortType: 'desc',
  params: filterParams,
});
crud.fetchRows();

const partyOptions = computed(() =>
  (crud.payload.value?.[cfg.partyListKey] || []).map(x => ({ value: x.id, label: x.name }))
);
const docOptions = computed(() => {
  const listKey = paymentKind === 'sale' ? 'sales' : 'purchases';
  return (crud.payload.value?.[listKey] || []).map(x => ({ value: x.id, label: x.Ref }));
});
const methodOptions = computed(() =>
  (crud.payload.value?.payment_methods || []).map(x => ({ value: x.id, label: x.name }))
);
const accountOptions = computed(() =>
  (crud.payload.value?.accounts || []).map(a => ({ value: a.id, label: a.account_name }))
);

const statTiles = computed(() => {
  const s = crud.payload.value?.stats || {};
  const n = k => Number(s[k]) || 0;
  return [
    { label: t('Payments'), value: n('count'), icon: WalletOutlined, color: '#6d28d9', tint: 'rgba(109, 40, 217, 0.12)' },
    { label: t('Amount'), value: money(n('total')), icon: DollarOutlined, color: '#10b981', tint: 'rgba(16, 185, 129, 0.12)' },
  ];
});

const columns = computed(() => [
  { title: t('Action'), key: 'actions', width: 70, align: 'center', fixed: 'left' },
  { title: t('date'), key: 'date', dataIndex: 'date', sorter: true, exportValue: r => date(r.date) },
  { title: t('Reference'), dataIndex: 'Ref', key: 'Ref', sorter: true },
  { title: t(cfg.docRefLabel === 'Choose_Sale_Ref' ? 'Sale_Ref' : 'Purchase_Ref'), key: 'doc' },
  { title: t(cfg.partyLabel), dataIndex: cfg.partyListKey === 'clients' ? 'client_name' : 'provider_name', key: 'party' },
  { title: t('ModePaiement'), key: 'payment_method', exportValue: r => r.payment_method },
  { title: t('Account'), dataIndex: 'account_name', key: 'account_name' },
  { title: t('Amount'), key: 'montant', dataIndex: 'montant', sorter: true, align: 'right', exportValue: r => money(r.montant) },
  { title: t('AddedBy'), dataIndex: 'user_name', key: 'user_name' },
]);

const exporting = ref(null);

async function exportList(kind) {
  exporting.value = kind;
  try {
    const data = await http.get(cfg.endpoint, {
      page: 1,
      SortField: crud.sortField.value,
      SortType: crud.sortType.value,
      search: crud.search.value,
      limit: -1,
      ...filterParams(),
    });
    const rows = data.payments || [];
    if (kind === 'xlsx') await exportExcel(cfg.endpoint, columns.value, rows);
    else await exportPdf(t(cfg.titleKey), columns.value, rows);
  } catch {
    message.error(t('InvalidData'));
  } finally {
    exporting.value = null;
  }
}

function onAction(key, record) {
  if (key === 'pdf') {
    http.download(`${cfg.pdfPrefix}/${record.id}`, `Payment-${record.Ref}.pdf`)
      .catch(() => message.error(t('InvalidData')));
  } else if (key === 'edit') {
    openEditPayment(record);
  } else if (key === 'delete') {
    removePayment(record);
  }
}

function removePayment(payment) {
  Modal.confirm({
    title: t('Delete_Title'),
    icon: createVNode(ExclamationCircleOutlined),
    content: t('Delete_Text'),
    okText: t('Delete_confirmButtonText'),
    okType: 'danger',
    cancelText: t('Delete_cancelButtonText'),
    async onOk() {
      try {
        await http.delete(`${cfg.endpoint}/${payment.id}`);
        message.success(t('Deleted_in_successfully'));
        crud.fetchRows();
      } catch {
        message.error(t('InvalidData'));
      }
    },
  });
}

const activeDoc = ref(null);
const payFormOpen = ref(false);
const payEditing = ref(null);
const paySaving = ref(false);
const payForm = ref({});
const docDue = ref(0);

const payChange = computed(() =>
  Math.max(0, (Number(payForm.value.received_amount) || 0) - (Number(payForm.value.montant) || 0))
);

const payMaxDue = computed(() => roundMoney(
  payEditing.value
    ? (Number(docDue.value) || 0) + (Number(payEditing.value.montant) || 0)
    : docDue.value
));

async function loadDocDue(docIdVal) {
  const ep = paymentKind === 'sale'
    ? `get_payments_by_sale/${docIdVal}`
    : `get_payments_by_purchase/${docIdVal}`;
  const data = await http.get(ep);
  docDue.value = data.due || 0;
}

async function beginPaymentOnDoc(doc) {
  if (!cfg.canPay(doc)) {
    message.warning(t('PaymentComplete'));
    return;
  }
  activeDoc.value = doc;
  payEditing.value = null;
  await loadDocDue(doc.id);
  payForm.value = {
    date: dayjs().format('YYYY-MM-DD'),
    montant: roundMoney(docDue.value),
    received_amount: roundMoney(docDue.value),
    payment_method_id: methodOptions.value[0]?.value ?? 2,
    account_id: undefined,
    notes: '',
  };
  payFormOpen.value = true;
}

async function openEditPayment(payment) {
  payEditing.value = payment;
  await loadDocDue(payment[cfg.docIdField]);
  payForm.value = {
    date: payment.date,
    montant: Number(payment.montant) || 0,
    received_amount: +((Number(payment.montant) || 0) + (Number(payment.change) || 0)).toFixed(2),
    payment_method_id: payment.payment_method_id,
    account_id: payment.account_id || undefined,
    notes: payment.notes || '',
  };
  activeDoc.value = { Ref: payment[cfg.docRefField], id: payment[cfg.docIdField] };
  payFormOpen.value = true;
}

async function submitPayment() {
  const f = payForm.value;
  const montant = Number(f.montant) || 0;
  if (montant <= 0 || !f.payment_method_id) {
    message.warning(t('Please_fill_the_form_correctly'));
    return;
  }
  if (montant > (Number(f.received_amount) || 0)) {
    message.warning(t('Paying_amount_is_greater_than_Received_amount'));
    return;
  }
  if (montant > payMaxDue.value) {
    message.warning(t('Paying_amount_is_greater_than_Grand_Total'));
    return;
  }
  paySaving.value = true;
  try {
    if (paymentKind === 'sale') {
      if (payEditing.value) {
        await http.put(`payment_sale/${payEditing.value.id}`, {
          sale_id: payEditing.value.sale_id,
          date: f.date,
          montant: montant.toFixed(2),
          received_amount: (Number(f.received_amount) || 0).toFixed(2),
          change: payChange.value.toFixed(2),
          payment_method_id: f.payment_method_id,
          account_id: f.account_id || null,
          notes: f.notes,
        });
      } else {
        await http.post('payment_sale', {
          sale_id: activeDoc.value.id,
          date: f.date,
          payments: [{ montant, payment_method_id: f.payment_method_id, account_id: f.account_id || null }],
          montant: montant.toFixed(2),
          received_amount: (Number(f.received_amount) || 0).toFixed(2),
          change: payChange.value.toFixed(2),
          payment_method_id: f.payment_method_id,
          account_id: f.account_id || null,
          notes: f.notes,
        });
      }
    } else if (payEditing.value) {
      await http.put(`payment_purchase/${payEditing.value.id}`, {
        purchase_id: payEditing.value.purchase_id,
        date: f.date,
        montant: montant.toFixed(2),
        received_amount: (Number(f.received_amount) || 0).toFixed(2),
        change: payChange.value.toFixed(2),
        payment_method_id: f.payment_method_id,
        account_id: f.account_id || null,
        notes: f.notes,
      });
    } else {
      await http.post('payment_purchase', {
        purchase_id: activeDoc.value.id,
        date: f.date,
        montant: montant.toFixed(2),
        received_amount: (Number(f.received_amount) || 0).toFixed(2),
        change: payChange.value.toFixed(2),
        payment_method_id: f.payment_method_id,
        account_id: f.account_id || null,
        notes: f.notes,
      });
    }
    message.success(payEditing.value ? t('Successfully_Updated') : t('Successfully_Created'));
    payFormOpen.value = false;
    crud.fetchRows();
  } catch (e) {
    message.error(e?.data?.message || t('InvalidData'));
  } finally {
    paySaving.value = false;
  }
}

const pickDocOpen = ref(false);
const pickDocId = ref(undefined);
const pickDocLoading = ref(false);
const eligibleDocOptions = ref([]);

async function openDocPicker() {
  pickDocOpen.value = true;
  pickDocId.value = undefined;
  pickDocLoading.value = true;
  try {
    const listParams = paymentKind === 'sale'
      ? {
          page: 1, limit: -1, SortField: 'id', SortType: 'desc',
          Ref: '', date: '', client_id: '', warehouse_id: '', statut: '',
          payment_statut: '', shipping_status: '',
        }
      : {
          page: 1, limit: -1, SortField: 'id', SortType: 'desc',
          Ref: '', date: '', provider_id: '', warehouse_id: '', statut: '',
          payment_statut: '',
        };
    const data = await http.get(cfg.listEndpoint, listParams);
    const rows = data[cfg.listRowsKey] || [];
    eligibleDocOptions.value = rows
      .filter(cfg.canPay)
      .map(r => ({
        value: r.id,
        label: [r.Ref, r.client_name || r.provider_name, money(r.due)].filter(Boolean).join(' — '),
      }));
  } catch {
    message.error(t('InvalidData'));
    pickDocOpen.value = false;
  } finally {
    pickDocLoading.value = false;
  }
}

async function confirmDocPick() {
  if (!pickDocId.value) {
    message.warning(t(cfg.docRefLabel));
    return Promise.reject();
  }
  pickDocOpen.value = false;
  try {
    const data = await http.get(`${cfg.listEndpoint}/${pickDocId.value}`);
    await beginPaymentOnDoc(data[cfg.docDetailKey] || {});
  } catch {
    message.error(t('InvalidData'));
  }
}

async function applyDocQuery() {
  const idField = cfg.docParam;
  const rawId = route.query[idField];
  if (!rawId) return;
  const docIdVal = Number(rawId);
  try {
    const data = await http.get(`${cfg.listEndpoint}/${docIdVal}`);
    await beginPaymentOnDoc(data[cfg.docDetailKey] || {});
  } catch {
    message.error(t('InvalidData'));
  }
  router.replace({ path: route.path, query: {} });
}

watch(
  () => crud.loading.value,
  loading => {
    if (!loading) applyDocQuery();
  },
  { immediate: true },
);

onMounted(() => {
  if (route.query.add === '1') openDocPicker();
});
</script>

<style scoped>
.stat-inner {
  display: flex;
  align-items: center;
  gap: 12px;
}
.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}
.stat-label {
  font-size: 12px;
  color: rgba(0, 0, 0, 0.55);
}
.stat-value {
  font-size: 20px;
  font-weight: 700;
}
</style>
