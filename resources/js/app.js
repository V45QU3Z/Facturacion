
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

// window.Vue = require('vue');
import Vue from 'vue'
import ElementUI from 'element-ui'
import Axios from 'axios'

import lang from 'element-ui/lib/locale/lang/es'
import locale from 'element-ui/lib/locale'
locale.use(lang)

//Vue.use(ElementUI)
Vue.use(ElementUI, {size: 'small'})
Vue.prototype.$eventHub = new Vue()
Vue.prototype.$http = Axios

// import VueCharts from 'vue-charts'
// Vue.use(VueCharts);
// import { TableComponent, TableColumn } from 'vue-table-component';
//
// Vue.component('table-component', TableComponent);
// Vue.component('table-column', TableColumn);
Vue.component('tenant-dashboard-index', require('../../modules/Dashboard/Resources/assets/js/views/index.vue'));

Vue.component('x-graph', require('./components/graph/src/Graph.vue'));
Vue.component('x-graph-line', require('./components/graph/src/GraphLine.vue'));

Vue.component('tenant-companies-form', require('./views/tenant/companies/form.vue'));
Vue.component('tenant-companies-logo', require('./views/tenant/companies/logo.vue'));
Vue.component('tenant-certificates-index', require('./views/tenant/certificates/index.vue'));
Vue.component('tenant-certificates-form', require('./views/tenant/certificates/form.vue'));
Vue.component('tenant-configurations-form', require('./views/tenant/configurations/form.vue'));
// Vue.component('tenant-establishments-form', require('./views/tenant/establishments/form.vue'));
// Vue.component('tenant-series-form', require('./views/tenant/series/form.vue'));
Vue.component('tenant-bank_accounts-index', require('./views/tenant/bank_accounts/index.vue'));
Vue.component('tenant-items-index', require('./views/tenant/items/index.vue'));
Vue.component('tenant-persons-index', require('./views/tenant/persons/index.vue'));
// Vue.component('tenant-customers-index', require('./views/tenant/customers/index.vue'));
// Vue.component('tenant-suppliers-index', require('./views/tenant/suppliers/index.vue'));
Vue.component('tenant-users-form', require('./views/tenant/users/form.vue'));
Vue.component('tenant-documents-index', require('./views/tenant/documents/index.vue'));
Vue.component('tenant-documents-invoice', require('./views/tenant/documents/invoice.vue'));
Vue.component('tenant-documents-invoicetensu', require('./views/tenant/documents/invoicetensu.vue'));
Vue.component('tenant-documents-note', require('./views/tenant/documents/note.vue'));
Vue.component('tenant-summaries-index', require('./views/tenant/summaries/index.vue'));
Vue.component('tenant-voided-index', require('./views/tenant/voided/index.vue'));
Vue.component('tenant-search-index', require('./views/tenant/search/index.vue'));
Vue.component('tenant-options-form', require('./views/tenant/options/form.vue'));
Vue.component('tenant-unit_types-index', require('./views/tenant/unit_types/index.vue'));
Vue.component('tenant-users-index', require('./views/tenant/users/index.vue'));
Vue.component('tenant-establishments-index', require('./views/tenant/establishments/index.vue'));
Vue.component('tenant-charge_discounts-index', require('./views/tenant/charge_discounts/index.vue'));
Vue.component('tenant-banks-index', require('./views/tenant/banks/index.vue'));
Vue.component('tenant-exchange_rates-index', require('./views/tenant/exchange_rates/index.vue'));
Vue.component('tenant-currency-types-index', require('./views/tenant/currency_types/index.vue'));
Vue.component('tenant-retentions-index', require('./views/tenant/retentions/index.vue'));
Vue.component('tenant-retentions-form', require('./views/tenant/retentions/form.vue'));
Vue.component('tenant-perceptions-index', require('./views/tenant/perceptions/index.vue'));
Vue.component('tenant-perceptions-form', require('./views/tenant/perceptions/form.vue'));
Vue.component('tenant-dispatches-index', require('./views/tenant/dispatches/index.vue'));
Vue.component('tenant-dispatches-form', require('./views/tenant/dispatches/form.vue'));
Vue.component('tenant-dispatches-create', require('./views/tenant/dispatches/create.vue'));
Vue.component('tenant-purchases-index', require('./views/tenant/purchases/index.vue'));
Vue.component('tenant-purchases-form', require('./views/tenant/purchases/form.vue'));
Vue.component('tenant-purchases-edit', require('./views/tenant/purchases/form_edit.vue'));

Vue.component('tenant-purchases-items', require('./views/tenant/dispatches/items.vue'));
Vue.component('tenant-attribute_types-index', require('./views/tenant/attribute_types/index.vue'));
Vue.component('tenant-calendar', require('./views/tenant/components/calendar.vue'));
Vue.component('tenant-warehouses', require('./views/tenant/components/warehouses.vue'));
Vue.component('tenant-calendar-quotation', require('./views/tenant/components/calendarquotations.vue'));

//Vue.component('tenant-calendar', require('./views/tenant/components/calendar.vue'));
Vue.component('tenant-product', require('./views/tenant/components/products.vue'));


Vue.component('tenant-tasks-lists', require('./views/tenant/tasks/lists.vue'));
Vue.component('tenant-tasks-form', require('./views/tenant/tasks/form.vue'));
Vue.component('tenant-reports-consistency-documents-lists', require('./views/tenant/reports/consistency-documents/lists.vue'));
Vue.component('tenant-contingencies-index', require('./views/tenant/contingencies/index.vue'));

Vue.component('tenant-quotations-index', require('./views/tenant/quotations/index.vue'));
Vue.component('tenant-quotations-form', require('./views/tenant/quotations/form.vue'));
Vue.component('tenant-quotations-edit', require('./views/tenant/quotations/form_edit.vue'));


Vue.component('tenant-sale-notes-index', require('./views/tenant/sale_notes/index.vue'));
Vue.component('tenant-sale-notes-form', require('./views/tenant/sale_notes/form.vue'));
Vue.component('tenant-pos-index', require('./views/tenant/pos/index.vue'));
Vue.component('cash-index', require('./views/tenant/cash/index.vue'));
Vue.component('tenant-card-brands-index', require('./views/tenant/card_brands/index.vue'));

// Modules
Vue.component('inventory-index', require('../../modules/Inventory/Resources/assets/js/inventory/index.vue'));
Vue.component('warehouses-index', require('../../modules/Inventory/Resources/assets/js/warehouses/index.vue'));
Vue.component('tenant-inventories-form', require('../../modules/Inventory/Resources/assets/js/config/form.vue'));
Vue.component('tenant-expenses-index', require('../../modules/Expense/Resources/assets/js/views/expenses/index.vue'));
Vue.component('tenant-expenses-form', require('../../modules/Expense/Resources/assets/js/views/expenses/form.vue'));
Vue.component('tenant-account-export', require('../../modules/Account/Resources/assets/js/views/account/export.vue'));
Vue.component('tenant-account-format', require('../../modules/Account/Resources/assets/js/views/account/format.vue'));
Vue.component('tenant-company-accounts', require('../../modules/Account/Resources/assets/js/views/company_accounts/form.vue'));

Vue.component('tenant-documents-not-sent', require('../../modules/Document/Resources/assets/js/views/documents/not_sent.vue'));
Vue.component('tenant-report-purchases-index', require('../../modules/Report/Resources/assets/js/views/purchases/index.vue'));
Vue.component('tenant-report-documents-index', require('../../modules/Report/Resources/assets/js/views/documents/index.vue'));
Vue.component('tenant-report-sale_notes-index', require('../../modules/Report/Resources/assets/js/views/sale_notes/index.vue'));
Vue.component('tenant-report-quotations-index', require('../../modules/Report/Resources/assets/js/views/quotations/index.vue'));
Vue.component('tenant-report-cash-index', require('../../modules/Report/Resources/assets/js/views/cash/index.vue'));
Vue.component('tenant-index-configuration', require('../../modules/BusinessTurn/Resources/assets/js/views/configurations/index.vue'));
Vue.component('tenant-report-document_hotels-index', require('../../modules/Report/Resources/assets/js/views/document_hotels/index.vue'));
Vue.component('tenant-offline-configurations-index', require('../../modules/Offline/Resources/assets/js/views/offline_configurations/index.vue'));

Vue.component('tenant-categories-index', require('../../modules/Item/Resources/assets/js/views/categories/index.vue'));
Vue.component('tenant-brands-index', require('../../modules/Item/Resources/assets/js/views/brands/index.vue'));

// System
Vue.component('system-clients-index', require('./views/system/clients/index.vue'));
Vue.component('system-clients-form', require('./views/system/clients/form.vue'));
Vue.component('system-users-form', require('./views/system/users/form.vue'));


Vue.component('system-plans-index', require('./views/system/plans/index.vue'));
Vue.component('system-plans-form', require('./views/system/plans/form.vue'));

Vue.component('x-input-service', require('./components/InputService.vue'));

Vue.component('tenant-items-ecommerce-index', require('./views/tenant/items_ecommerce/index.vue'));
Vue.component('tenant-ecommerce-cart', require('./views/tenant/ecommerce/cart_dropdown.vue'));
Vue.component('tenant-tags-index', require('./views/tenant/tags/index.vue'));
Vue.component('tenant-promotions-index', require('./views/tenant/promotions/index.vue'));

Vue.component('tenant-item-sets-index', require('./views/tenant/item_sets/index.vue'));

Vue.component('tenant-orders-index', require('./views/tenant/orders/index.vue'));

//Cuenta
Vue.component('tenant-account-payment-index', require('./views/tenant/account/payment_index.vue'));
Vue.component('tenant-account-configuration-index', require('./views/tenant/account/configuration.vue'));


const app = new Vue({
    el: '#main-wrapper'
});
