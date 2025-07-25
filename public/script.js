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

// Check devices before requesting media
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
  if (!stream) return; // if no media stream available

  addVideoStream(myVideo, stream);

  myPeer.on('call', call => {
    call.answer(stream);
    const video = document.createElement('video');
    call.on('stream', userVideoStream => {
      addVideoStream(video, userVideoStream);
    });
  });

  socket.on('user-connected', userId => {
    connectToNewUser(userId, stream);
  });
}).catch(error => {
  console.error('Media device error:', error.name, error.message);
  alert('Could not access camera or microphone:\n' + error.message);
});

socket.on('user-disconnected', userId => {
  if (peers[userId]) peers[userId].close();
});

myPeer.on('open', id => {
  socket.emit('join-room', ROOM_ID, id);
});

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

function addVideoStream(video, stream) {
  video.srcObject = stream;
  video.addEventListener('loadedmetadata', () => {
    video.play();
  });
  videoGrid.append(video);
}
