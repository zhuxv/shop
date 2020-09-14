// 注册一个名为 user-address-create-and-edit 的组件
Vue.component('user-addresses-create-and-edit', {
    // 组件的数据
    data() {
        return {
            province: '', // 省
            city: '', // 市
            district: '' //地区
        }
    },
    methods: {
        // 把参数 val 中的值保存在数组中
        onDistrictChanged( val ) {
            if ( val.length === 3 ) {
                this.province = val[0];
                this.city = val[1];
                this.district = val[2];
            }
        }
    }
});