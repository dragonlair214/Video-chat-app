// ------------------ Setup ------------------
const socket = io('/');
const videoGrid = document.getElementById('video-grid');

const myPeer = new Peer(undefined, {
  host: location.hostname,
  port: location.protocol === 'https:' ? 443 : 3000,
  path: '/peerjs',
  secure: location.protocol === 'https:',
  config: { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] }
});

const myVideo = document.createElement('video');
myVideo.muted = true;

const peers = {};            // userId -> MediaConnection
let myStream = null;         // local camera/mic stream
let screenStream = null;     // active screen stream (if any)
let screenSharing = false;

// Controls (must exist in your HTML)
const muteBtn  = document.getElementById('muteBtn');
const videoBtn = document.getElementById('videoBtn');
const shareBtn = document.getElementById('shareBtn');
const leaveBtn = document.getElementById('leaveBtn');

// ------------------ Media acquisition ------------------
navigator.mediaDevices.enumerateDevices()
  .then(devices => {
    const hasVideo = devices.some(d => d.kind === 'videoinput');
    const hasAudio = devices.some(d => d.kind === 'audioinput');
    if (!hasVideo && !hasAudio) {
      alert('No camera or microphone found.');
      return null;
    }
    return navigator.mediaDevices.getUserMedia({ video: hasVideo, audio: hasAudio });
  })
  .then(stream => {
    if (!stream) return;

    myStream = stream;
    addVideoStream(myVideo, myStream);

    // Answer calls
    myPeer.on('call', call => {
      call.answer(myStream);
      const video = document.createElement('video');
      call.on('stream', userVideoStream => addVideoStream(video, userVideoStream));
      call.on('close', () => video.remove());
    });

    // New user joined
    socket.on('user-connected', userId => connectToNewUser(userId, myStream));
  })
  .catch(err => {
    console.error('Media device error:', err.name, err.message);
    alert('Could not access camera or microphone:\n' + err.message);
  });

// Handle remote leaves
socket.on('user-disconnected', userId => {
  if (peers[userId]) {
    peers[userId].close();
    delete peers[userId];
  }
});

// Join room
myPeer.on('open', id => {
  socket.emit('join-room', ROOM_ID, id);
});

// ------------------ Peer helpers ------------------
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

// ------------------ Controls ------------------
// Toggle Mute / Unmute
if (muteBtn) {
  muteBtn.addEventListener('click', () => {
    if (!myStream) return;
    const audioTrack = myStream.getAudioTracks()[0];
    if (!audioTrack) return;

    audioTrack.enabled = !audioTrack.enabled;
    muteBtn.textContent = audioTrack.enabled ? '🎤 Mute' : '🔇 Unmute';
  });
}

// Toggle Video On / Off
if (videoBtn) {
  videoBtn.addEventListener('click', () => {
    if (!myStream) return;
    const videoTrack = myStream.getVideoTracks()[0];
    if (!videoTrack) return;

    videoTrack.enabled = !videoTrack.enabled;
    videoBtn.textContent = videoTrack.enabled ? '📷 Stop Video' : '📷 Start Video';
  });
}

// Share Screen (swap outgoing video track on all connections)
if (shareBtn) {
  shareBtn.addEventListener('click', async () => {
    try {
      if (!screenSharing) {
        // Start screen share
        screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: false });
        const screenTrack = screenStream.getVideoTracks()[0];

        replaceOutgoingVideoTrack(screenTrack);
        screenSharing = true;
        shareBtn.textContent = '🖥️ Stop Sharing';

        // When user stops from browser UI
        screenTrack.onended = () => {
          stopScreenShare();
        };
      } else {
        // Stop sharing, revert to camera
        stopScreenShare();
      }
    } catch (e) {
      console.error('Error sharing screen:', e);
    }
  });
}

function stopScreenShare() {
  if (!screenSharing) return;
  const camTrack = myStream?.getVideoTracks()?.[0];
  if (camTrack) replaceOutgoingVideoTrack(camTrack);

  // stop the screen stream tracks
  screenStream?.getTracks().forEach(t => t.stop());
  screenStream = null;
  screenSharing = false;
  if (shareBtn) shareBtn.textContent = '🖥️ Share Screen';
}

// Replace the video track being sent to all peers
function replaceOutgoingVideoTrack(newTrack) {
  // Update our local preview (myVideo)
  const senderTrackLabel = newTrack.label || 'video';
  const newStream = new MediaStream([newTrack, ...myStream.getAudioTracks()]);
  myVideo.srcObject = newStream;

  // For every active peer connection, swap the sender track
  Object.values(peers).forEach(call => {
    const pc = call.peerConnection;         // RTCPeerConnection (PeerJS)
    const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
    if (sender) sender.replaceTrack(newTrack);
  });
}

// Leave room (stop tracks, close peers, disconnect, redirect)
if (leaveBtn) {
  leaveBtn.addEventListener('click', () => {
    try {
      // stop our local tracks
      myStream?.getTracks().forEach(t => t.stop());
      screenStream?.getTracks().forEach(t => t.stop());

      // close all calls
      Object.values(peers).forEach(call => call.close());
      for (const k in peers) delete peers[k];

      // disconnect transports
      socket.disconnect();
      myPeer.disconnect();
      myPeer.destroy();
    } catch (e) {
      console.warn('Error while leaving:', e);
    } finally {
      // navigate away
      window.location.href = 'index.html';
    }
  });
}

// ------------------ Chat (unchanged) ------------------
const chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message-input');
const messagesList = document.getElementById('messages');

if (chatForm && messageInput && messagesList) {
  chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (messageInput.value.trim()) {
      socket.emit('chat-message', messageInput.value);
      appendMessage(`You: ${messageInput.value}`);
      messageInput.value = '';
    }
  });

  socket.on('chat-message', (message) => {
    appendMessage(message);
  });

  function appendMessage(message) {
    const li = document.createElement('li');
    li.textContent = message;
    messagesList.appendChild(li);
    messagesList.scrollTop = messagesList.scrollHeight;
  }
}

