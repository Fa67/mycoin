<template>
<div class="container">
    <div class="columns">
        <div class="column is-7">
            <table class="table  is-striped is-narrow is-fullwidth">
            <thead>
            <tr>
                <th>
                    <a @click.prevent="loadMyOrders(selected_instrument)" class="button is-small" >
                        <span class="icon">
                            <i class="fa fa-sync"></i>
                        </span>
                    </a>
                </th>
                <th colspan="2">
                    Orders <b>{{ this.instrument }}</b>
                </th>
                <th>
                    Price
                </th>
                <th>
                    Amount
                </th>
                <th>
                    Avail
                </th>
                <th>
                    Date
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(item, index) in myorders['orders']">
                <td>
                    <a @click.prevent="deleteOrder(item.id, item.fund_id)" class="button is-small" >
                        <span class="icon">
                            <i class="fa fa-trash"></i>
                        </span>
                    </a>
                    <a @click.prevent="replaceOrder(index, item.id, item.fund_id)" class="button is-small" >
                        <span class="icon">
                            <i class="fa fa-fire"></i>
                        </span>
                    </a>
                </td>
                
                <td :class="item['side']">{{ item.fund_id }} {{ item['side'] }}<br>{{ item['id'] }}</td>
                
                <td>{{ item['type'] }}<br>{{ item['status'] }}</td>
                
                <td>{{ item['price'] }}</td>
                <td>{{ item['amount'] }}</td>
                <td>{{ item['amount_unfilled'] }}</td>
                <td>{{ item['date'] }}</td>

            </tr>
            </tbody>
            </table>
            <div class="control">
                {{ spread }} %
                <label class="radio">
                <input type="radio" name="action" value="buy"  v-model="order_action">
                BUY
                </label>
                <label class="radio">
                <input type="radio" name="action" value="sell" v-model="order_action" checked>
                SELL
                </label>
                <input class="input" type="number" placeholder="Price" v-model.number="order_price">
                <input class="input" type="number" placeholder="Amount" v-model.number="order_amount">
                <button @click.prevent="submitOrder()" class="button is-small" >GO</button>
                <br>Total: {{total_order}}
            </div>



        </div>
        
        <div class="column is-2" v-for="(operation, index_operation) in ['bids', 'asks']">
            <table class="table  is-striped is-narrow is-fullwidth">
            <thead>
            <tr>
                <th colspan="2">
                    {{ selected_instrument }} {{ operation }}
                    
                </th>
            </tr>
            </thead>
            <tbody>
                
            <tr v-for="(item, index) in orderbook[operation]">
                <td>{{ item.price }}</td>
                <td>{{ item.amount }}</td>
            </tr>
            </tbody>
            </table>
        </div>
    </div>
</div>

</template>

<script>
import Pusher from 'pusher-js' // import Pusher
    export default {
        props: {
            'instrument':{
                type:String,
                default: "BTCEUR"
            }
        },
        data() {
            return {
                orderbook: [],
                newtrade: [ ],
                selected_instrument: "",
                loadingMyOrders: false,
                myorders: [],
                subscribed_channel: false,
                pusher: null,

                order_price:0,
                order_amount:0,
                order_action:"BUY",

                spread: "0"

            }
        },
        watch: {
            // whenever question changes, this function will run
            instrument: function (newInstrument) {
                this.initInstrument(newInstrument);
                
            }
        },
        computed: {
            
            total_order: function () {
                var amount = parseFloat(this.order_amount);
                var price = parseFloat(this.order_price);
                
                return amount*price;
                
            },
        },
        methods: {
            deleteOrder(id, fund_id) {
                console.log("Delete order"+id+"on instrument: "+fund_id);
                axios.delete('api/rock/order/'+fund_id, { data: {
                    id: id,
                    fund_id: fund_id
                }}).then(function (response) {
                    console.log(response);
                    console.log(fund_id);
                    this.loadMyOrders(fund_id);
                }).catch(function (error) {
                    console.log(error);
                });
            },
            replaceOrder(index,id, fund_id) {
                var current_order= this.myorders['orders'][index];
                console.log("Replace order"+id+"on instrument: "+fund_id+" position"+index);
                console.log(current_order.id);
                console.log(current_order.side);
                var current_bid = this.orderbook.bids[0].price;
                var current_ask = this.orderbook.asks[0].price
                if (current_order.side =="buy") {

                    this.order_price = current_bid+0.01;    
                    this.order_amount = current_order.amount;
                }
                //console.log(this.orderbook.bids[0].price);
            },

            submitOrder() {
                var amount = parseFloat(this.order_amount);
                var price = parseFloat(this.order_price);
                console.log(amount);
                console.log(price);
                console.log(amount*price);
                axios.put('api/rock/order/'+this.selected_instrument, {
                    fund_id: this.selected_instrument,
                    action: this.order_action,
                    amount: amount,
                    price: price,
                    side: this.order_action
                }).then(function (response) {
                    console.log(response);
                }).catch(function (error) {
                    console.log(error);
                });                
            },
            loadMyOrders(instrument) {

                this.loadingMyOrders=true;
                axios.get('api/rock/orders/'+instrument).then( (response) => {
                    console.log(response.data);
                    this.loadingMyOrders=false;
                    this.myorders = response.data;
                    return response.data;
                })
            },
            loadOrderbook(){
                this.loadingOrderBook=true;
                axios.get('api/rock/orderbook/'+this.instrument+'/5').then( (response) => {
                    console.log(response.data.bids);
                    this.orderbook_loading=false;
                    this.orderbook_bids = response.data.bids;
                    this.orderbook_asks = response.data.asks;

                    return response.data;
                    
                })
                
            },
            calculate_spread: function () {
                //console.log("Computed Spread: ");
                //console.log(this.orderbook.asks);
                
                if (this.orderbook.asks != undefined) {
                    this.spread =  (Math.abs(1 - (this.orderbook.asks[0].price / this.orderbook.bids[0].price))*100).toFixed(3);

                }
                
            },
            subscribe (instrument) {
                this.newtrade=[];
                var wantBind=false;
                if (! this.pusher) {
                    this.pusher = new Pusher('bb1fafdf79a00453b5af');
                    wantBind = true;
                }
                
                console.log("Subscribe to orderbook:"+instrument);
                if (this.subscribed_channel) {
                    this.pusher.unsubscribe(this.subscribed_channel);
                    this.orderbook=[];
                }
                this.pusher.subscribe(instrument);
                this.subscribed_channel=instrument;        
                if (wantBind) {
                    this.pusher.bind('orderbook', data => {
                        this.orderbook = data;
                        this.calculate_spread();
                    });

                }
            },
            initInstrument(instrument) {
                this.selected_instrument = instrument;
                this.subscribe(instrument);
                console.log(this.loadMyOrders(instrument));

            }

        },
        mounted() {
            

            this.initInstrument(this.instrument);
            console.log('Component mounted, with initial instrument: '+this.instrument);
        }
    }
</script>

<style>

td.buy {
    background-color: lightgreen;   
}
td.sell {
    background-color: lightpink;   
}

</style>

