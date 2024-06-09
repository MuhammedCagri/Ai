let chunks = [];
let recorder;
let audioStream;
let isRecording = false;

const recordBtn = document.getElementById("recordBtn");
const micIcon = document.getElementById("micIcon");
const transcriptionResult = document.getElementById("transcriptionResult");

recordBtn.addEventListener("click", async () => {
  if (!isRecording) {
    recordBtn.classList.add("recording");

    audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });
    recorder = new MediaRecorder(audioStream);

    recorder.ondataavailable = (event) => chunks.push(event.data);
    recorder.onstop = sendRecording;

    recorder.start();
    isRecording = true;
  } else {
    recordBtn.classList.remove("recording");

    recorder.stop();
    audioStream.getTracks().forEach((track) => track.stop());
    isRecording = false;
  }
});

function sendRecording() {
  const blob = new Blob(chunks, { type: "audio/mp3" });
  chunks = [];

  const formData = new FormData();
  formData.append("audio", blob, "recording.mp3");

  fetch("transcribe.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      transcriptionResult.textContent = data.transcription;
      //             transcriptionResult.textContent = data;
      //       frameCommunicationSend({ action: "put", message: data.transcription });
    })
    .catch((error) => {
      console.error("Error:", error);
      transcriptionResult.textContent = "transcription error";
    });
}
