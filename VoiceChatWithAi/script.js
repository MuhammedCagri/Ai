let isListening = false;
let isPlaying = false;
let mediaRecorder;
let audioChunks = [];

function toggleListening() {
  const speechButton = document.getElementById("speechButton");
  const audioPlayer = document.getElementById("audioPlayer");

  if (isPlaying) {
    audioPlayer.pause();
    speechButton.classList.remove("playing");
    speechButton.innerHTML = "Talk";
    isPlaying = false;
  } else if (isListening) {
    stopListening();
  } else {
    startListening();
  }
}

function startListening() {
  isListening = true;
  const speechButton = document.getElementById("speechButton");
  speechButton.classList.add("active");
  speechButton.innerHTML =
    '<div class="bars"><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div></div>';

  navigator.mediaDevices
    .getUserMedia({ audio: true })
    .then((stream) => {
      mediaRecorder = new MediaRecorder(stream);
      mediaRecorder.start();
      mediaRecorder.ondataavailable = (event) => {
        audioChunks.push(event.data);
      };
    })
    .catch((error) => console.error("Error accessing microphone:", error));
}

function stopListening() {
  isListening = false;
  const speechButton = document.getElementById("speechButton");
  speechButton.classList.remove("active");
  speechButton.innerHTML = "Talk";

  mediaRecorder.stop();
  mediaRecorder.onstop = () => {
    const audioBlob = new Blob(audioChunks, { type: "audio/mpeg" });
    audioChunks = [];
    const formData = new FormData();
    formData.append("audio", audioBlob, "audio.mp3");

    speechButton.classList.add("loading");
    speechButton.innerHTML = '<div class="loader"></div>';

    fetch("execute.php", {
      method: "POST",
      body: formData,
      mode: "cors",
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        const audioUrl = data.url;
        const audioPlayer = document.getElementById("audioPlayer");
        audioPlayer.src = audioUrl;
        audioPlayer.hidden = false;

        const playPromise = audioPlayer.play();

        if (playPromise !== undefined) {
          playPromise
            .then(() => {
              speechButton.classList.remove("loading");
              speechButton.classList.add("playing");
              speechButton.innerHTML =
                '<div class="bars"><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div></div>';
              isPlaying = true;
              audioPlayer.onended = () => {
                speechButton.classList.remove("playing");
                speechButton.innerHTML = "Talk";
                isPlaying = false;
              };
            })
            .catch((error) => {
              console.error("Automatic playback failed:", error);
              // Kullanıcıdan manuel oynatma isteği için bir UI elemanı gösterin
            });
        }
      })
      .catch((error) => console.error("Error:", error));
  };
}
