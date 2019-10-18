<template>
  <div class="hello">
    <h1>{{ msg }}</h1>
    <label>
        玩家ID：
        <input type="text" :value="playerId">
    </label>
    <button @click="matchPlayer">匹配</button>
    <div v-if="matching" style="display: inline">
        匹配中……
    </div>
    <!--
    <h2>Essential Links</h2>
    <ul>
      <li>
        <a
          href="https://vuejs.org"
          target="_blank"
        >
          Core Docs
        </a>
      </li>
      <li>
        <a
          href="https://forum.vuejs.org"
          target="_blank"
        >
          Forum
        </a>
      </li>
      <li>
        <a
          href="https://chat.vuejs.org"
          target="_blank"
        >
          Community Chat
        </a>
      </li>
      <li>
        <a
          href="https://twitter.com/vuejs"
          target="_blank"
        >
          Twitter
        </a>
      </li>
      <br>
      <li>
        <a
          href="http://vuejs-templates.github.io/webpack/"
          target="_blank"
        >
          Docs for This Template
        </a>
      </li>
    </ul>
    <h2>Ecosystem</h2>
    <ul>
      <li>
        <a
          href="http://router.vuejs.org/"
          target="_blank"
        >
          vue-router
        </a>
      </li>
      <li>
        <a
          href="http://vuex.vuejs.org/"
          target="_blank"
        >
          vuex
        </a>
      </li>
      <li>
        <a
          href="http://vue-loader.vuejs.org/"
          target="_blank"
        >
          vue-loader
        </a>
      </li>
      <li>
        <a
          href="https://github.com/vuejs/awesome-vue"
          target="_blank"
        >
          awesome-vue
        </a>
      </li>
    </ul>
    -->
  </div>
</template>

<script>
export default {
  name: 'HelloWorld',
  data () {
    return {
      msg: 'tiandaye',
      websock: null,
      // 随机生成一个玩家id
      playerId: 'player_' + Math.round(Math.random() * 1000),
      // 房间号
      roomId: '',
      // 是否在匹配
      matching: false
    }
  },
  beforeCreate () {
    console.log('Test beforeCreate')
  },
  created () {
    console.log('Test created')
    // 初始化websocket
    this.initWebSocket()
  },
  mounted () {
    console.log('Test mounted')
  },
  beforeDestroy () {
    console.log('Test beforeDestroy')
  },
  destroyed () {
    console.log('Test destroyed')
    // 离开路由之后断开websocket连接
    this.websock.close()
  },
  beforeUpdate () {
    console.log('Test beforeUpdate')
  },
  updated () {
    console.log('Test updated')
  },
  methods: {
    // 匹配玩家
    matchPlayer () {
      let actions = {'code': 600}
      this.websocketsend(actions)

      this.matching = true
    },
    // 开始房间
    startRoom () {
      let actions = {'code': 601, 'room_id': this.roomId}
      this.websocketsend(actions)

      this.matching = false
    },
    initWebSocket () { // 初始化websocket
      const wsuri = 'ws://127.0.0.1:8811?player_id=' + this.playerId
      this.websock = new WebSocket(wsuri)
      this.websock.onmessage = this.websocketonmessage
      this.websock.onopen = this.websocketonopen
      this.websock.onerror = this.websocketonerror
      this.websock.onclose = this.websocketclose
    },
    websocketonopen () { // 连接建立之后执行send方法发送数据
    },
    websocketonerror () { // 连接建立失败重连
      this.initWebSocket()
    },
    websocketonmessage (e) { // 数据接收
      let message = e.data
      try {
        message = JSON.parse(e.data)
      } catch (err) {
        console.log(err) // 可执行
      }

      // let responseData = message.data
      // switch (message.code) {
      //   case 1001:
      //     this.roomId = responseData.room_id
      //     this.startRoom()
      //     break
      // }

      let code = message['code']
      let data = message['data']
      switch (code) {
        // 匹配成功
        case 1001:
          this.roomId = data.room_id
          this.startRoom()
          break

        default:
          break
      }

      // let message = JSON.parse(e.data)
      console.log(message)
    },
    websocketsend (data) { // 数据发送
      this.websock.send(JSON.stringify(data))
    },
    websocketclose (e) { // 关闭
      console.log('断开连接', e)
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
h1, h2 {
  font-weight: normal;
}
ul {
  list-style-type: none;
  padding: 0;
}
li {
  display: inline-block;
  margin: 0 10px;
}
a {
  color: #42b983;
}
</style>
