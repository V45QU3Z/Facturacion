<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @close="close" @open="create" :close-on-click-modal="false">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.identity_document_type_id}">
                            <label class="control-label">Tipo Doc. Identidad <span class="text-danger">*</span></label>
                            <el-select v-model="form.identity_document_type_id" filterable  popper-class="el-select-identity_document_type" dusk="identity_document_type_id" @change="changeIdentityDocType">
                                <el-option v-for="option in identity_document_types" :key="option.id" :value="option.id" :label="option.description"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.identity_document_type_id" v-text="errors.identity_document_type_id[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.number}">
                            <label class="control-label">Número <span class="text-danger">*</span></label>

                            <div v-if="api_service_token != false">
                                <x-input-service :identity_document_type_id="form.identity_document_type_id" v-model="form.number" @search="searchNumber"></x-input-service>
                            </div>
                            <div v-else>
                                <el-input v-model="form.number" :maxlength="maxLength" dusk="number">
                                    <template v-if="form.identity_document_type_id === '6' || form.identity_document_type_id === '1'">
                                        <el-button type="primary" slot="append" :loading="loading_search" icon="el-icon-search" @click.prevent="searchCustomer">
                                            <template v-if="form.identity_document_type_id === '6'">
                                                SUNAT
                                            </template>
                                            <template v-if="form.identity_document_type_id === '1'">
                                                RENIEC
                                            </template>
                                        </el-button>
                                    </template>
                                </el-input>
                            </div>

                            <small class="form-control-feedback" v-if="errors.number" v-text="errors.number[0]"></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.name}">
                            <label class="control-label">Nombre <span class="text-danger">*</span></label>
                            <el-input v-model="form.name" dusk="name"></el-input>
                            <small class="form-control-feedback" v-if="errors.name" v-text="errors.name[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.trade_name}">
                            <label class="control-label">Nombre comercial</label>
                            <el-input v-model="form.trade_name" dusk="trade_name"></el-input>
                            <small class="form-control-feedback" v-if="errors.trade_name" v-text="errors.trade_name[0]"></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group" :class="{'has-danger': errors.country_id}">
                            <label class="control-label">País</label>
                            <el-select v-model="form.country_id" filterable dusk="country_id">
                                <el-option v-for="option in countries" :key="option.id" :value="option.id" :label="option.description"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.country_id" v-text="errors.country_id[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group" :class="{'has-danger': errors.department_id}">
                            <label class="control-label">Departamento</label>
                            <el-select v-model="form.department_id" filterable @change="filterProvince" popper-class="el-select-departments" dusk="department_id">
                                <el-option v-for="option in all_departments" :key="option.id" :value="option.id" :label="option.description"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.department_id" v-text="errors.department_id[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group" :class="{'has-danger': errors.province_id}">
                            <label class="control-label">Provincia</label>
                            <el-select v-model="form.province_id" filterable @change="filterDistrict" popper-class="el-select-provinces" dusk="province_id">
                                <el-option v-for="option in provinces" :key="option.id" :value="option.id" :label="option.description"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.province_id" v-text="errors.province_id[0]"></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group" :class="{'has-danger': errors.province_id}">
                            <label class="control-label">Distrito</label>
                            <el-select v-model="form.district_id" filterable popper-class="el-select-districts" dusk="district_id">
                                <el-option v-for="option in districts" :key="option.id" :value="option.id" :label="option.description"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.district_id" v-text="errors.district_id[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group" :class="{'has-danger': errors.address}">
                            <label class="control-label">Dirección</label>
                            <el-input v-model="form.address" dusk="address"></el-input>
                            <small class="form-control-feedback" v-if="errors.address" v-text="errors.address[0]"></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.telephone}">
                            <label class="control-label">Teléfono</label>
                            <el-input v-model="form.telephone" dusk="telephone"></el-input>
                            <small class="form-control-feedback" v-if="errors.telephone" v-text="errors.telephone[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.email}">
                            <label class="control-label">Correo electrónico</label>
                            <el-input v-model="form.email" dusk="email"></el-input>
                            <small class="form-control-feedback" v-if="errors.email" v-text="errors.email[0]"></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6" v-if="form.state">
                        <div class="form-group" >
                            <label class="control-label">Estado del Contribuyente</label>
                            <template v-if="form.state == 'ACTIVO'">
                                <el-alert   :title="`${form.state}`"  type="success"   show-icon :closable="false"></el-alert>
                            </template>
                            <template v-else>
                                <el-alert   :title="`${form.state}`"  type="error"   show-icon :closable="false"></el-alert>
                            </template>
                        </div>

                    </div>
                    <div class="col-md-6" v-if="form.condition">
                        <div class="form-group" >
                            <label class="control-label">Condición del Contribuyente</label>
                            <template v-if="form.condition == 'HABIDO'">
                                <el-alert   :title="`${form.condition}`"  type="success"   show-icon :closable="false"></el-alert>
                            </template>
                            <template v-else>
                                <el-alert   :title="`${form.condition}`"  type="error"   show-icon :closable="false"></el-alert>
                            </template>
                        </div>

                    </div>
                </div>
                <div class="row mt-2" v-if="type === 'suppliers'">
                    <div class="col-md-6 center-el-checkbox">
                        <div class="form-group" :class="{'has-danger': errors.perception_agent}">
                            <el-checkbox v-model="form.perception_agent">¿Es agente de percepción?</el-checkbox><br>
                            <small class="form-control-feedback" v-if="errors.perception_agent" v-text="errors.perception_agent[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6" v-if="type === 'suppliers'" v-show="form.perception_agent">
                        <div class="form-group"  >
                            <label class="control-label">Porcentaje de percepción</label>

                            <el-input v-model="form.percentage_perception"></el-input>
                        </div>
                    </div>
                </div>
                <!-- <div class="row mt-2">
                    <div class="col-md-12">
                        <a href="#" @click.prevent="clickAddAddress">Agregar otra dirección</a>
                    </div>
                    <div class="col-md-12">
                        <div class="row" v-for="row in form.more_address">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="control-label">Ubigeo</label>
                                    <el-cascader :options="locations" v-model="row.location_id" :clearable="true" filterable></el-cascader>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="form-group">
                                    <label class="control-label">Dirección</label>
                                    <el-input v-model="row.address"></el-input>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
            <div class="form-actions text-right mt-4">
                <el-button @click.prevent="close()">Cancelar</el-button>
                <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
            </div>
        </form>
    </el-dialog>
</template>

<script>

    import {serviceNumber} from '../../../mixins/functions'

    export default {
        mixins: [serviceNumber],
        props: ['showDialog', 'type', 'recordId', 'external', 'document_type_id'],
        data() {
            return {
                loading_submit: false,
                titleDialog: null,
                resource: 'persons',
                errors: {},
                api_service_token:false,
                form: {},
                countries: [],
                all_departments: [],
                all_provinces: [],
                all_districts: [],
                provinces: [],
                districts: [],
                locations: [],
                identity_document_types: []
            }
        },
        created() {
            this.initForm()
            this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.api_service_token = response.data.api_service_token
                    // console.log(this.api_service_token)

                    this.countries = response.data.countries
                    this.all_departments = response.data.departments;
                    this.all_provinces = response.data.provinces;
                    this.all_districts = response.data.districts;
                    this.identity_document_types = response.data.identity_document_types;
                    this.locations = response.data.locations;
                })

        },
        computed: {
            maxLength: function () {
                if (this.form.identity_document_type_id === '6') {
                    return 11
                }
                if (this.form.identity_document_type_id === '1') {
                    return 8
                }
            }
        },
        methods: {
            initForm() {
                this.errors = {}
                this.form = {
                    id: null,
                    type: this.type,
                    identity_document_type_id: '6',
                    number: '',
                    name: null,
                    trade_name: null,
                    country_id: 'PE',
                    department_id: null,
                    province_id: null,
                    district_id: null,
                    address: null,
                    telephone: null,
                    condition: null,
                    state: null,
                    email: null,
                    perception_agent: false,
                    percentage_perception:0,
                    more_address: []
                }
            },
            create() {
                if(this.external) {
                    if(this.document_type_id === '01') {
                        this.form.identity_document_type_id = '6'
                    }
                    if(this.document_type_id === '03') {
                        this.form.identity_document_type_id = '1'
                    }
                }
                if(this.type === 'customers') {
                    this.titleDialog = (this.recordId)? 'Editar Cliente':'Nuevo Cliente'
                }
                if(this.type === 'suppliers') {
                    this.titleDialog = (this.recordId)? 'Editar Proveedor':'Nuevo Proveedor'
                }
                if (this.recordId) {
                    this.$http.get(`/${this.resource}/record/${this.recordId}`)
                        .then(response => {
                            this.form = response.data.data
                            this.filterProvinces()
                            this.filterDistricts()
                        })
                }
            },
            clickAddAddress() {
                this.form.more_address.push({
                    location_id: [],
                    address: null,
                })
            },
            submit() {
                this.loading_submit = true
                this.$http.post(`/${this.resource}`, this.form)
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success(response.data.message)
                            if (this.external) {
                                this.$eventHub.$emit('reloadDataPersons', response.data.id)
                            } else {
                                this.$eventHub.$emit('reloadData')
                            }
                            this.close()
                        } else {
                            this.$message.error(response.data.message)
                        }
                    })
                    .catch(error => {
                        if (error.response.status === 422) {
                            this.errors = error.response.data
                        } else {
                            console.log(error)
                        }
                    })
                    .then(() => {
                        this.loading_submit = false
                    })
            },
            changeIdentityDocType(){
                (this.recordId == null) ? this.setDataDefaultCustomer() : null
            },
            setDataDefaultCustomer(){

                if(this.form.identity_document_type_id == '0'){
                    this.form.number = '99999999'
                    this.form.name = "Clientes - Varios"
                }else{
                    this.form.number = ''
                    this.form.name = null
                }

            },
            close() {
                this.$emit('update:showDialog', false)
                this.initForm()
            },
            searchCustomer() {
                this.searchServiceNumberByType()
            },
            searchNumber(data) {
                this.form.name = (this.form.identity_document_type_id === '1')?data.nombre_completo:data.nombre_o_razon_social;
                this.form.trade_name = (this.form.identity_document_type_id === '6')?data.nombre_o_razon_social:'';
                this.form.location_id = data.ubigeo;
                this.form.address = data.direccion;
                this.form.department_id = (data.ubigeo) ? data.ubigeo[0]:null;
                this.form.province_id = (data.ubigeo) ? data.ubigeo[1]:null;
                this.form.district_id = (data.ubigeo) ? data.ubigeo[2]:null;
                this.form.condition = data.condicion;
                this.form.state = data.estado;

                this.filterProvinces()
                this.filterDistricts()
//                this.form.addresses[0].telephone = data.telefono;
           },
        }
    }
</script>
