/* eslint-disable vue/require-v-for-key */
<template>
  <div class="hello">
    <h1>{{ msg }}</h1>
    <div v-if="onlinePlayer">
        当前在线的玩家人数：{{onlinePlayer}}
    </div>
    <div v-if="playersRank">
        <br>
        游戏排行榜：
        <br>
        <template v-for="times, player in playersRank">
            玩家：{{player}} 胜利次数：{{times}}
            <br>
        </template>
    </div>
    <div v-if="roomId">
        <div>
            房间号：{{roomId}}
        </div>
        <div v-if="timeLimit">
            剩余时间：{{timeLimit}}
        </div>
    </div>
    <div v-if="playerType">
        本局玩家类型：{{playerTypeArr[playerType]}}
    </div>
    <label>
        <!--玩家-->
        ID：
        <input type="text" :value="playerId">
    </label>
    <button @click="matchPlayer">匹配</button>
    <div v-if="matching" style="display: inline">
        匹配中……
    </div>
    <div v-else>
        <div v-if="!roomId" style="padding-top: 5px;">
            对手ID：
            <input type="text" v-model="opponentId">
            <button @click="makeChallenge">挑战</button>
        </div>
    </div>
    <br />
    <hr />
    <div v-if="mapData" style="display: flex">
        <div>
            <template v-for="column in mapData">
                <div>
                    <template v-for="item in column">
                        <div v-if="item==playerId" class="gameItem player">{{playerId}}</div>
                        <div v-else-if="item==0" class="gameItem wall">墙</div>
                        <div v-else-if="item==1" class="gameItem road">路</div>
                        <div v-else class="gameItem player">{{item}}</div>
                    </template>
                </div>
            </template>
        </div>

        <div>
            <template v-for="i in 5">
                <div @mouseup="removeClickClass">
                    <template v-for="j in 5">
                        <div v-if="i==2&&j==3" @mousedown="clickDirect('up')" data-direction="up"
                             class="gameItem gameButton">上
                        </div>
                        <div v-else-if="i==3&&j==2" @mousedown="clickDirect('left')" data-direction="left"
                             class="gameItem gameButton">左
                        </div>
                        <div v-else-if="i==3&&j==4" @mousedown="clickDirect('right')" data-direction="right"
                             class="gameItem gameButton">右
                        </div>
                        <div v-else-if="i==4&&j==3" @mousedown="clickDirect('down')" data-direction="down"
                             class="gameItem gameButton">下
                        </div>
                        <div v-else class="gameItem space">无</div>
                    </template>
                </div>
            </template>
        </div>
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
      // 在线人数
      onlinePlayer: null,
      // 随机生成一个玩家id
      playerId: '',
      // playerId: 'player_' + Math.round(Math.random() * 1000),
      // 房间号
      roomId: '',
      // 是否在匹配
      matching: false,
      // 地图数据
      mapData: [],
      // 胜利者
      winner: '',
      // 排行榜
      playersRank: null,
      // 邀请的对手
      opponentId: '',
      // 玩家类型
      playerType: null,
      playerTypeArr: [0, '寻找者', '躲藏者'],
      // 倒计时
      timeLimit: null,
      // setInterval句柄
      timerId: null
    }
  },
  beforeCreate () {
    console.log('Test beforeCreate')
  },
  created () {
    console.log('Test created')
    // 初始化playerID
    this.initPlayerId()
    // 初始化websocket
    this.initWebSocket()
    // pc端通过JavaScript监听键盘点击事件来实现这个功能
    this.initDirectionKey()

    this.getServerInfo()
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
    getServerInfo () {
      // var that = this
      let _params = {
        'a': 'get_online_player'
      }
      this.$axios.get('http://127.0.0.1:8812', {
        params: _params
      })
        .then((response) => {
          console.group('getServerInfo-get_online_player success')
          console.log(response)
          console.log(response.data)
          this.onlinePlayer = response.data.online_player
          // cb(response.data)
          console.groupEnd()
        }, (err) => {
          console.group('getServerInfo-get_online_player error')
          // errorCb()
          console.log(err)
          console.groupEnd()
        })

      _params = {
        'a': 'get_player_rank'
      }
      this.$axios.get('http://127.0.0.1:8812', {
        params: _params
      })
        .then((response) => {
          console.group('getServerInfo-get_player_rank success')
          console.log(response)
          console.log(response.data)
          this.playersRank = response.data.players_rank
          // cb(response.data)
          console.groupEnd()
        }, (err) => {
          console.group('getServerInfo-get_player_rank error')
          // errorCb()
          console.log(err)
          console.groupEnd()
        })
    },
    // 初始化player id
    initPlayerId () {
      var inputPlayerId = this.getUrlParam('player_id')
      if (inputPlayerId !== '') {
        this.playerId = inputPlayerId
      } else {
        this.playerId = 'player_' + Math.round(Math.random() * 1000)
      }
    },
    // 获得url参数
    getUrlParam (paramName) {
      var url = document.location.toString()
      var arrObj = url.split('?')
      if (arrObj.length > 1) {
        var arrPara = arrObj[1].split('&')
        var arr
        for (var i = 0; i < arrPara.length; i++) {
          arr = arrPara[i].split('=')
          if (arr !== null && arr[0] === paramName) {
            return arr[1]
          }
        }
        return ''
      } else {
        return ''
      }
    },
    // 初始化方向key
    initDirectionKey () {
      // 为啥这里要用var that = this呀？直接在闭包里用this不行吗？的确，在PHP的闭包中，$this对象会自动从父作用域进行绑定，但在JavaScript闭包中的this会在函数真正被调用执行的时候才确定，如果想达到PHP的自动绑定效果，需要使用ES6语法的箭头函数来编写闭包。
      var that = this
      document.onkeydown = function () {
        if (event.keyCode === 38) {
          console.log('up')
          that.clickDirect('up')
        } else if (event.keyCode === 37) {
          console.log('left')
          that.clickDirect('left')
        } else if (event.keyCode === 39) {
          console.log('right')
          that.clickDirect('right')
        } else if (event.keyCode === 40) {
          console.log('down')
          that.clickDirect('down')
        }
      }
    },
    // 邀请对手
    makeChallenge () {
      if (!this.opponentId) {
        alert('请输入对手ID')
        return
      }
      let actions = {
        'code': 603,
        'opponent_id': this.opponentId
      }
      this.websocketsend(actions)
    },
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
    // 点击选择方向按钮
    clickDirect (direction) {
      let actions = {'code': 602, 'direction': direction}
      this.websocketsend(actions)
      this.addClickClass(direction)
    },
    // 判断是否存在某个class
    hasClass (ele, cls) {
      return ele.className.match(new RegExp('(\\s|^)' + cls + '(\\s|$)'))
    },
    // 为指定的dom元素添加样式
    addClass (ele, cls) {
      if (!this.hasClass(ele, cls)) ele.className += ' ' + cls
    },
    // 删除指定dom元素的样式
    removeClass (ele, cls) {
      if (this.hasClass(ele, cls)) {
        let reg = new RegExp('(\\s|^)' + cls + '(\\s|$)')
        ele.className = ele.className.replace(reg, ' ')
      }
    },
    // 添加点击的class
    addClickClass (direction) {
      let divs = document.getElementsByClassName('gameButton')
      for (let div of divs) {
        if (div.dataset.direction === direction) {
          this.addClass(div, 'clickButton')
        }
      }
    },
    // 移除点击的class
    removeClickClass () {
      let divs = document.getElementsByClassName('gameButton')
      for (let div of divs) {
        this.removeClass(div, 'clickButton')
      }
    },
    // ws相关
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

        // // 游戏数据
        // case 1004:
        //   this.mapData = data.map_data
        //   break

        // 游戏数据
        case 1004:
          this.mapData = data.map_data
          if (!this.playerType) {
            let players = data.players
            this.playerType = players[this.playerId].type
          }
          if (!this.timerId) {
            this.timeLimit = data.time_limit
            var that = this
            this.timerId = setInterval(function () {
              that.timeLimit--
            }, 1000)
          }
          break

        // 游戏结束
        case 1005:
          this.winner = data.winner
          setTimeout(function () {
            alert('游戏结束~胜者是：' + data.winner)
          }, 200)
          break

        // 玩家退出, 通知对手
        case 1006:
          alert('你的敌人跑路了')
          break

        // 对手不在线
        case 1007:
          alert('对手不在线')
          break

        // 接受挑战
        case 1008:
          var challengerId = data.challenger_id
          var msg = '玩家 ' + challengerId + ' 邀请你进行对战，是否接受'
          let actions = {
            'code': 604,
            'challenger_id': challengerId
          }
          if (!confirm(msg)) {
            actions = {
              'code': 605,
              'challenger_id': challengerId
            }
          }
          this.websocketsend(actions)
          break

        // 拒绝挑战
        case 1009:
          alert('对方拒绝了你的挑战')
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
      if (e.code === 4000) {
        alert('该player_id已在线')
      }
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.gameItem {
    display: inline-block;
    width: 100px;
    height: 100px;
    line-height: 100px;
    border: 1px solid black;
    text-align: center;
}

.wall {
    background-color: black;
}

.road {
    color: white;
}

.player {
}

.gameButton {
    background-color: #efefef;
}

.space {
    background-color: white;
    color: white;
    border: 0;
    margin: 1px;
}

.clickButton {
    background: #dddddd;
}

/* 默认的 */
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
