import asyncio
import edge_tts

async def debug_stream():
    comm = edge_tts.Communicate("Klik tombol Buat Pengajuan Baru.", "id-ID-ArdiNeural", boundary="WordBoundary")
    async for chunk in comm.stream():
        print("CHUNK:", chunk.get("type"), {k: v for k, v in chunk.items() if k != "data"})

if __name__ == '__main__':
    asyncio.run(debug_stream())
