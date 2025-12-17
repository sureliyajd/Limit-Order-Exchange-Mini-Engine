import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

function createEcho() {
  return new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
      headers: {
        get Authorization() {
          return `Bearer ${localStorage.getItem('auth_token')}`
        },
      },
    },
  })
}

const echo = createEcho()

export default echo
