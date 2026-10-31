import asyncio
import json
import os
import sys

from emergentintegrations.llm.chat import LlmChat, UserMessage, TextDelta, StreamDone


async def main() -> int:
    payload = json.loads(sys.stdin.read())
    api_key = os.environ.get("EMERGENT_LLM_KEY")
    if not api_key:
        print("EMERGENT_LLM_KEY belum diatur", file=sys.stderr)
        return 2

    history = [{"role": "system", "content": payload["system"]}]
    history += [{"role": m["role"], "content": m["content"]} for m in payload.get("history", [])]

    chat = LlmChat(
        api_key=api_key,
        session_id=payload["session_id"],
        system_message=payload["system"],
        initial_messages=history,
    ).with_model("anthropic", payload["model"]).with_params(max_tokens=payload.get("max_tokens", 1200))

    async for event in chat.stream_message(UserMessage(text=payload["message"])):
        if isinstance(event, TextDelta):
            sys.stdout.write(event.content)
            sys.stdout.flush()
        elif isinstance(event, StreamDone):
            break
    return 0


if __name__ == "__main__":
    try:
        sys.exit(asyncio.run(main()))
    except Exception as exc:
        print(str(exc), file=sys.stderr)
        sys.exit(1)
