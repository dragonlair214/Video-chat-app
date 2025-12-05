// ------------------ Setup ------------------

// Cloud Run requires absolute URL for Socket.IO
const socket = io(window.location.origin);

// ------------------ User identity ------------------
const urlParams = new URLSearchParams(window.location.search);
const COUNSELOR_ID = urlParams.get("counselor_id") || null;

// HTML elements
const videoGrid = document.getElementById('video-grid');

// PeerJS setup for Cloud Run / HTTPS
const myPeer = new Peer(undefined, {
  host: window.location.hostname,
  secure: true,
  port: window.location.port || (window.location.protocol === 'https:' ? 443 : 80),
  path: '/peerjs',
  config: {
    iceServers: [
      { urls: 'stun:stun.l.google.com:19302' }
    ]
  }
});

// Video elements
const myVideo = document.createElement('video');
myVideo.muted = true;

const peers = {};
let myStream = null;
let screenStream = null;
let screenSharing = false;

// Toolbar control buttons
const muteBtn  = document.getElementById('toggle-mic');
const videoBtn = document.getElementById('toggle-cam');
const shareBtn = document.getElementById('share-screen');
const leaveBtn = document.getElementById('leave');

// ------------------ Media acquisition ------------------

navigator.mediaDevices.enumerateDevices()
  .then(devices => {
    const hasVideo = devices.some(d => d.kind === 'videoinput');
    const hasAudio = devices.some(d => d.kind === 'audioinput');

    if (!hasVideo && !hasAudio) {
      alert('No camera or microphone found.');
      return null;
    }

    return navigator.mediaDevices.getUserMedia({
      video: hasVideo,
      audio: hasAudio
    });
  })
  .then(stream => {
    if (!stream) return;

    myStream = stream;
    addVideoStream(myVideo, myStream);

    myPeer.on('call', call => {
      call.answer(myStream);

      const video = document.createElement('video');
      call.on('stream', userVideoStream => addVideoStream(video, userVideoStream));
      call.on('close', () => video.remove());
    });

    // Updated to accept name + userId
    socket.on('user-connected', ({ userId, name }) => {
      console.log(`${name} connected`);
      connectToNewUser(userId, myStream);
    });
  })
  .catch(err => {
    alert('Could not access camera or microphone: ' + err.message);
    console.error(err);
  });

// ------------------ Room & Peer events ------------------

myPeer.on('open', id => {
  socket.emit('join-room', ROOM_ID, id, COUNSELOR_ID); // <-- UPDATED
});

socket.on('user-disconnected', userId => {
  if (peers[userId]) {
    peers[userId].close();
    delete peers[userId];
  }
});

// ------------------ Helper functions ------------------

function connectToNewUser(userId, stream) {
  const call = myPeer.call(userId, stream);
  const video = document.createElement('video');

  call.on('stream', userVideoStream => addVideoStream(video, userVideoStream));
  call.on('close', () => video.remove());

  peers[userId] = call;
}

function addVideoStream(video, stream) {
  video.srcObject = stream;
  video.addEventListener('loadedmetadata', () => video.play());
  videoGrid.append(video);
}

// ------------------ Toolbar Controls ------------------

if (muteBtn) {
  muteBtn.addEventListener('click', () => {
    const audioTrack = myStream?.getAudioTracks()[0];
    if (!audioTrack) return;

    audioTrack.enabled = !audioTrack.enabled;
    muteBtn.textContent = audioTrack.enabled ? '🎤 Mute' : '🔇 Unmute';
  });
}

if (videoBtn) {
  videoBtn.addEventListener('click', () => {
    const videoTrack = myStream?.getVideoTracks()[0];
    if (!videoTrack) return;

    videoTrack.enabled = !videoTrack.enabled;
    videoBtn.textContent = videoTrack.enabled ? '🎥 Stop Video' : '🎥 Start Video';
  });
}

if (shareBtn) {
  shareBtn.addEventListener('click', async () => {
    try {
      if (!screenSharing) {
        screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
        const screenTrack = screenStream.getVideoTracks()[0];

        replaceOutgoingVideoTrack(screenTrack);
        screenSharing = true;
        shareBtn.textContent = '🛑 Stop Sharing';

        screenTrack.onended = () => stopScreenShare();
      } else {
        stopScreenShare();
      }
    } catch (e) {
      console.error('Screen share error:', e);
    }
  });
}

function stopScreenShare() {
  if (!screenSharing) return;

  const camTrack = myStream?.getVideoTracks()[0];
  if (camTrack) replaceOutgoingVideoTrack(camTrack);

  screenStream?.getTracks().forEach(t => t.stop());
  screenStream = null;
  screenSharing = false;
  shareBtn.textContent = '🖥️ Share Screen';
}

function replaceOutgoingVideoTrack(newTrack) {
  const newPreview = new MediaStream([newTrack, ...myStream.getAudioTracks()]);
  myVideo.srcObject = newPreview;

  Object.values(peers).forEach(call => {
    const pc = call.peerConnection;
    const sender = pc.getSenders().find(s => s.track?.kind === 'video');
    if (sender) sender.replaceTrack(newTrack);
  });
}

// Leave room
if (leaveBtn) {
  leaveBtn.addEventListener('click', () => {
    try {
      myStream?.getTracks().forEach(t => t.stop());
      screenStream?.getTracks().forEach(t => t.stop());
      Object.values(peers).forEach(call => call.close());
      socket.disconnect();
      myPeer.destroy();
    } finally {
      window.location.href = '/';
    }
  });
}

// ------------------ Chat ------------------

const chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message-input');
const messagesList = document.getElementById('messages');

if (chatForm) {
  chatForm.addEventListener('submit', e => {
    e.preventDefault();
    if (!messageInput.value.trim()) return;

    socket.emit('chat-message', messageInput.value);
    appendMessage(`You: ${messageInput.value}`);
    messageInput.value = '';
  });

  // Updated to handle {name, text}
  socket.on('chat-message', ({ name, text }) => {
    appendMessage(`${name}: ${text}`);
  });

  function appendMessage(msg) {
    const li = document.createElement('li');
    li.textContent = msg;
    messagesList.appendChild(li);
    messagesList.scrollTop = messagesList.scrollHeight;
  }
}
