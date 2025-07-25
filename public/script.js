const socket = io('/');
const videoGrid = document.getElementById('video-grid');

const myPeer = new Peer(undefined, {
  host: location.hostname,
  port: location.protocol === 'https:' ? 443 : 3000,
  path: '/peerjs',
  secure: location.protocol === 'https:',
  config: {
    iceServers: [
      { urls: 'stun:stun.l.google.com:19302' }
    ]
  }
});

const myVideo = document.createElement('video');
myVideo.muted = true;
const peers = {};

// Step 1: Get audio & video stream from user
navigator.mediaDevices.enumerateDevices().then(devices => {
  const hasVideo = devices.some(device => device.kind === 'videoinput');
  const hasAudio = devices.some(device => device.kind === 'audioinput');

  if (!hasVideo && !hasAudio) {
    alert('No camera or microphone found.');
    return;
  }

  return navigator.mediaDevices.getUserMedia({
    video: hasVideo,
    audio: hasAudio
  });
}).then(stream => {
  if (!stream) return;

  // Add local video stream
  addVideoStream(myVideo, stream);

  // Answer calls from others
  myPeer.on('call', call => {
    call.answer(stream);
    const video = document.createElement('video');
    call.on('stream', userVideoStream => {
      addVideoStream(video, userVideoStream);
    });
  });

  // When a user connects
  socket.on('user-connected', userId => {
    connectToNewUser(userId, stream);
  });

  // Log audio track info (optional debug)
  stream.getAudioTracks().forEach(track => {
    console.log('Local mic track:', track.label, track.enabled);
  });

}).catch(error => {
  console.error('Media device error:', error.name, error.message);
  alert('Could not access camera or microphone:\n' + error.message);
});

// Step 2: Remove disconnected peers
socket.on('user-disconnected', userId => {
  if (peers[userId]) peers[userId].close();
});

// Step 3: Join the room
myPeer.on('open', id => {
  socket.emit('join-room', ROOM_ID, id);
});

// Step 4: Call new user
function connectToNewUser(userId, stream) {
  const call = myPeer.call(userId, stream);
  const video = document.createElement('video');

  call.on('stream', userVideoStream => {
    addVideoStream(video, userVideoStream);
  });

  call.on('close', () => {
    video.remove();
  });

  peers[userId] = call;
}

// Step 5: Append video to screen
function addVideoStream(video, stream) {
  video.srcObject = stream;
  video.addEventListener('loadedmetadata', () => {
    video.play();
  });

  // Just make sure remote audio is not muted
  video.muted = false;

  videoGrid.append(video);
}
