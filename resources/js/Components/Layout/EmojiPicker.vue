<template>
    <div class="emoji-picker-wrapper" ref="pickerWrapper">
        <!-- Кнопка открытия -->
        <button
            type="button"
            class="emoji-trigger"
            @click="togglePicker"
            :title="modelValue ? 'Изменить эмодзи' : 'Добавить эмодзи'"
        >
            <span v-if="modelValue" class="selected-emoji">{{ modelValue }}</span>
            <i v-else class="fa-regular fa-face-smile"></i>
            <span v-if="modelValue" class="remove-emoji" @click.stop="removeEmoji" title="Удалить эмодзи">
                <i class="fa-solid fa-xmark"></i>
            </span>
        </button>

        <!-- Выпадающий пикер -->
        <Transition name="picker">
            <div v-if="isOpen" class="emoji-picker" @click.stop>
                <!-- Поиск -->
                <div class="picker-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Поиск эмодзи..."
                        ref="searchInput"
                    />
                </div>

                <!-- Категории (табы) -->
                <div class="picker-tabs">
                    <button
                        v-for="category in categories"
                        :key="category.key"
                        type="button"
                        class="picker-tab"
                        :class="{ active: activeCategory === category.key }"
                        @click="activeCategory = category.key"
                        :title="category.label"
                    >
                        <span class="tab-icon">{{ category.icon }}</span>
                        <span class="tab-label">{{ category.label }}</span>
                    </button>
                </div>

                <!-- Сетка эмодзи -->
                <div class="picker-grid">
                    <div v-if="filteredEmojis.length === 0" class="picker-empty">
                        <i class="fa-solid fa-face-meh"></i>
                        <span>Ничего не найдено</span>
                    </div>
                    <button
                        v-for="emoji in filteredEmojis"
                        :key="emoji"
                        type="button"
                        class="emoji-item"
                        @click="selectEmoji(emoji)"
                    >
                        {{ emoji }}
                    </button>
                </div>

                <!-- Подсказка -->
                <div class="picker-footer">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Эмодзи добавится в начало названия</span>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script>
export default {
    name: 'EmojiPicker',

    props: {
        modelValue: {
            type: String,
            default: ''
        }
    },

    emits: ['update:modelValue'],

    data() {
        return {
            isOpen: false,
            searchQuery: '',
            activeCategory: 'food',
            categories: [
                {
                    key: 'food',
                    label: 'Еда',
                    icon: '🍔',
                    emojis: ['🍕', '🍔', '🍟', '🌭', '🥪', '🌮', '🌯', '🥙', '🧆', '🥚', '🍳', '🥘', '🍲', '🥣', '🥗', '🍿', '🧈', '🧂', '🥫', '🍱', '🍘', '🍙', '🍚', '🍛', '🍜', '🍝', '🍠', '🍢', '🍣', '🍤', '🍥', '🥮', '🍡', '🥟', '🥠', '🥡', '🦀', '🦞', '🦐', '🦑', '🧇', '🥞', '🍰', '🎂', '🧁', '🥧', '🍫', '🍬', '🍭', '🍮', '🍯', '☕', '🍵', '🧃', '🥤', '🧋', '🍼', '🥛', '🍺', '🍻', '🥂', '🍷', '🥃', '🍸', '🍹', '🧉', '🍾']
                },
                {
                    key: 'clothes',
                    label: 'Одежда',
                    icon: '👕',
                    emojis: ['👕', '👚', '👔', '👗', '👙', '👘', '🥻', '🩱', '🩲', '🩳', '👖', '🧥', '🧦', '🧤', '🧣', '🎩', '🧢', '👒', '🎓', '⛑️', '👑', '💍', '👝', '👛', '👜', '💼', '🎒', '🧳', '👓', '🕶️', '🥿', '👠', '👡', '👢', '👞', '👟', '🥾', '🩴']
                },
                {
                    key: 'sport',
                    label: 'Спорт',
                    icon: '⚽',
                    emojis: ['⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳', '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛼', '🛷', '⛸️', '🥌', '🎿', '⛷️', '🏂', '🪂', '🏋️', '🤼', '🤸', '⛹️', '🤺', '🤾', '🏌️', '🏇', '🧘', '🏄', '🏊', '🤽', '🚣', '🧗', '🚵', '🚴', '🏆', '🥇', '🥈', '🥉', '🏅', '🎖️', '🏵️', '🎗️', '🎫', '🎟️']
                },
                {
                    key: 'tech',
                    label: 'Техника',
                    icon: '💻',
                    emojis: ['💻', '🖥️', '🖨️', '⌨️', '🖱️', '🖲️', '💽', '💾', '💿', '📀', '📱', '📲', '☎️', '📞', '📟', '📠', '🔋', '🔌', '💡', '🔦', '🕯️', '🪔', '🧯', '🛢️', '💸', '💵', '💴', '💶', '💷', '🪙', '💰', '💳', '💎', '⚖️', '🪜', '🧰', '🪛', '🔧', '🔨', '⚒️', '🛠️', '⛏️', '🪚', '🔩', '⚙️', '🪤', '🧲', '🔫', '💣', '🧨', '🪓', '🔪', '🗡️', '⚔️', '🛡️', '🚬', '⚰️', '🪦', '⚱️', '🏺', '🔮', '📿', '🧿', '💈', '⚗️', '🔭', '🔬', '🕳️', '🩹', '🩺', '💊', '💉', '🩸', '🧬', '🦠', '🧫', '🧪', '🌡️', '🧹', '🪠', '🧺', '🧻', '🚽', '🚰', '🚿', '🛁', '🛀', '🧼', '🪥', '🪒', '🧽', '🪣', '🧴']
                },
                {
                    key: 'beauty',
                    label: 'Красота',
                    icon: '💄',
                    emojis: ['💄', '💋', '👄', '🦷', '👅', '👂', '👃', '👁️', '👀', '🧠', '🫀', '🫁', '🦴', '🦵', '🦶', '🦾', '🦿', '🦻', '👶', '🧒', '👦', '👧', '🧑', '👨', '👩', '🧓', '👴', '👵', '💇', '💅', '🤳', '💪', '🦵', '🦶', '👣', '👈', '👉', '👆', '🖕', '👇', '✌️', '🤞', '🖖', '🤘', '🤙', '👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤌', '🤏', '🤝', '🙏', '✍️', '💅', '🤳', '💪', '🦵', '🦶', '👂', '🦻', '👃', '🧠', '🫀', '🫁', '🦷', '', '👀', '👁️', '👅', '👄']
                },
                {
                    key: 'auto',
                    label: 'Авто',
                    icon: '🚗',
                    emojis: ['🚗', '🚕', '🚙', '🚌', '🚎', '🏎️', '🚓', '🚑', '🚒', '🚐', '🛻', '🚚', '🚛', '🚜', '🦯', '🦽', '🦼', '🛴', '🚲', '🛵', '🏍️', '🛺', '🚨', '🚔', '🚍', '🚘', '🚖', '🚡', '🚠', '🚟', '🚃', '🚋', '🚞', '🚝', '🚄', '🚅', '🚈', '🚂', '🚆', '🚇', '🚊', '🚉', '✈️', '🛫', '🛬', '🛩️', '💺', '🛰️', '🚀', '🛸', '🚁', '🛶', '⛵', '🚤', '🛥️', '🛳️', '⛴️', '🚢', '⚓', '🪝', '⛽', '🚧', '🚦', '🚥', '🚏', '🗺️', '🗿', '🗽', '🗼', '🏰', '🏯', '🏟️', '🎡', '🎢', '🎠', '⛲', '⛱️', '🏖️', '🏝️', '🏜️', '🌋', '⛰️', '🏔️', '🗻', '🏕️', '🛖', '🏠', '🏡', '🏘️', '🏚️', '🏗️', '🏭', '🏢', '🏬', '🏣', '🏤', '🏥', '🏦', '🏨', '🏪', '🏫', '🏩', '💒', '🏛️', '⛪', '🕌', '🕍', '🛕', '🕋', '⛩️']
                },
                {
                    key: 'home',
                    label: 'Дом',
                    icon: '🏠',
                    emojis: ['🏠', '🏡', '🏘️', '🏚️', '🏗️', '🏭', '🏢', '🏬', '🏣', '🏤', '🏥', '🏦', '🏨', '🏪', '🏫', '🏩', '💒', '🏛️', '⛪', '🕌', '🕍', '🛕', '🕋', '⛩️', '🛖', '🏰', '🏯', '🏟️', '🎡', '🎢', '🎠', '⛲', '⛱️', '🏖️', '🏝️', '🏜️', '🌋', '⛰️', '🏔️', '🗻', '🏕️', '🛖', '🏠', '🏡', '🏘️', '🏚️', '🛋️', '🪑', '🚪', '🪟', '🛏️', '🛌', '🧸', '🪆', '🖼️', '🪞', '🪤', '🧺', '🧻', '🪣', '🧴', '🧼', '🪥', '🪒', '🧽', '🧹', '🪠', '🧺', '🧻', '🚽', '🚰', '🚿', '🛁', '🛀', '🪤', '🧲', '🔧', '🔨', '⚒️', '🛠️', '⛏️', '🪚', '🔩', '⚙️', '🪝', '🧰', '🪛', '🪜']
                },
                {
                    key: 'business',
                    label: 'Бизнес',
                    icon: '💼',
                    emojis: ['💼', '📊', '📈', '📉', '📇', '📅', '📆', '🗓️', '📋', '📁', '📂', '🗂️', '🗄️', '📌', '📍', '📎', '🖇️', '📏', '📐', '✂️', '🗃️', '🗳️', '🗃️', '🗄️', '🖊️', '🖋️', '✒️', '🖌️', '🖍️', '📝', '✏️', '🔍', '🔎', '🔏', '🔐', '🔒', '🔓', '💰', '💴', '💵', '💶', '💷', '🪙', '💸', '💳', '🧾', '💹', '💲', '🏧', '🛒', '🎁', '🎀', '🎊', '🎉', '🎈', '🎏', '🎐', '🎑', '🧧', '🎗️', '🎟️', '🎫', '🏆', '🏅', '🥇', '🥈', '🥉', '⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳', '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛼', '🛷', '⛸️', '🥌', '🎿', '⛷️', '🏂', '🪂', '🏋️', '🤼', '🤸', '⛹️', '🤺', '🤾', '🏌️', '🏇', '🧘', '🏄', '🏊', '🤽', '🚣', '🧗', '🚵', '🚴', '🏆']
                },
                {
                    key: 'nature',
                    label: 'Природа',
                    icon: '🌳',
                    emojis: ['🌳', '🌲', '🌴', '🌵', '🌾', '🌿', '☘️', '🍀', '🍁', '🍂', '🍃', '🍄', '🌰', '🐚', '🪨', '🌷', '🌹', '🥀', '🌺', '🌸', '🌼', '🌻', '🌞', '🌝', '🌛', '🌜', '🌚', '🌕', '🌖', '🌗', '🌘', '🌑', '🌒', '🌓', '🌔', '🌙', '🌎', '🌍', '🌏', '💫', '⭐', '🌟', '✨', '⚡', '☄️', '💥', '🔥', '🌪️', '🌈', '☀️', '🌤️', '⛅', '🌥️', '☁️', '🌦️', '🌧️', '⛈️', '🌩️', '🌨️', '❄️', '☃️', '⛄', '🌬️', '💨', '💧', '💦', '☔', '☂️', '🌊', '🌫️', '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐻‍❄️', '🐨', '🐯', '🦁', '🐮', '🐷', '🐽', '🐸', '🐵', '🙈', '🙉', '🙊', '🐒', '🐔', '🐧', '🐦', '🐤', '🐣', '🐥', '🦆', '🦅', '🦉', '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🪱', '🐛', '🦋', '🐌', '🐞', '🐜', '🪰', '🪲', '🪳', '🦟', '🦗', '🕷️', '🕸️', '🦂', '🐢', '🐍', '🦎', '🦖', '🦕', '🐙', '🦑', '🦐', '🦞', '🦀', '🐡', '🐠', '🐟', '🐬', '🐳', '🐋', '🦈', '🐊', '🐅', '🐆', '🦓', '🦍', '🦧', '🐘', '🦛', '🦏', '🐪', '🐫', '🦒', '🦘', '🦬', '🐃', '🐂', '🐄', '🐎', '🐖', '🐏', '🐑', '🦙', '🐐', '🦌', '🐕', '🐩', '🦮', '🐕‍🦺', '🐈', '🐈‍⬛', '🪶', '🐓', '🦃', '🦚', '🦜', '🦢', '🦩', '🕊️', '🐇', '🦝', '🦨', '🦡', '🦫', '🦦', '🦥', '🐁', '🐀', '🐿️', '🦔']
                },
                {
                    key: 'party',
                    label: 'Праздники',
                    icon: '🎉',
                    emojis: ['🎉', '🎊', '🎈', '🎁', '🎀', '🎗️', '🎟️', '🎫', '🎖️', '🏆', '🏅', '🥇', '🥈', '🥉', '⚽', '🎃', '🎄', '🎆', '🎇', '🧨', '✨', '🎈', '🎉', '🎊', '🎋', '🎍', '🎎', '🎏', '🎐', '🎑', '🧧', '🎀', '🎁', '🎗️', '🎟️', '🎫', '🎖️', '🏆', '🏅', '🥇', '🥈', '🥉', '⚽', '🎲', '🎯', '🎳', '🎮', '🎰', '🧩', '🎭', '🎨', '🧵', '🧶', '🎼', '🎵', '🎶', '🎙️', '🎚️', '🎛️', '🎤', '🎧', '📻', '🎷', '🪗', '🎸', '🎹', '🎺', '🎻', '🪕', '🥁', '🪘', '📱', '📲', '☎️', '📞', '📟', '📠', '🔋', '🔌', '💻', '🖥️', '🖨️', '⌨️', '🖱️', '🖲️', '💽', '💾', '💿', '📀', '🧮', '🎥', '🎞️', '📽️', '🎬', '📺', '📷', '📸', '📹', '📼', '🔍', '🔎', '🕯️', '💡', '🔦', '🏮', '🪔', '📔', '📕', '📖', '📗', '📘', '📙', '📚', '📓', '📒', '📃', '📜', '📄', '📰', '🗞️', '📑', '🔖', '🏷️', '💰', '🪙', '💴', '💵', '💶', '💷', '💸', '💳', '🧾', '💹', '✉️', '📧', '📨', '📩', '📤', '📥', '📦', '📫', '📪', '📬', '📭', '📮', '🗳️', '✏️', '✒️', '🖋️', '🖊️', '🖌️', '🖍️', '📝', '💼', '📁', '📂', '🗂️', '📅', '📆', '🗒️', '🗓️', '📇', '📈', '📉', '📊', '📋', '📌', '📍', '📎', '🖇️', '📏', '📐', '✂️', '🗃️', '🗄️', '🗑️', '🔒', '🔓', '🔏', '🔐', '🔑', '🗝️', '🔨', '🪓', '⛏️', '⚒️', '🛠️', '🗡️', '⚔️', '🔫', '🪃', '🏹', '🛡️', '🪚', '🔧', '🪛', '🔩', '⚙️', '🗜️', '⚖️', '🦯', '🔗', '⛓️', '🪝', '🧰', '🧲', '🪜', '⚗️', '🧪', '🧫', '🧬', '🔬', '🔭', '📡', '💉', '🩸', '💊', '🩹', '🩺', '🚪', '🛗', '🪞', '🪟', '🛏️', '🛋️', '🪑', '🚽', '🪠', '🚿', '🛁', '🪤', '🪒', '🧴', '🧷', '🧹', '🧺', '🧻', '🪣', '🧼', '🪥', '🧽', '🧯', '🛒', '🚬', '⚰️', '🪦', '⚱️', '🗿', '🪧']
                },
                {
                    key: 'symbols',
                    label: 'Символы',
                    icon: '❤️',
                    emojis: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉️', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎', '♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐', '♑', '♒', '♓', '🆔', '⚛️', '🉑', '☢️', '☣️', '📴', '📳', '🈶', '🈚', '🈸', '🈺', '🈷️', '✴️', '🆚', '💮', '🉐', '㊙️', '㊗️', '🈴', '🈵', '🈹', '🈲', '🅰️', '🅱️', '🆎', '🆑', '🅾️', '🆘', '❌', '⭕', '🛑', '⛔', '📛', '🚫', '💯', '💢', '♨️', '🚷', '🚯', '🚳', '🚱', '🔞', '📵', '🚭', '❗', '❕', '❓', '❔', '‼️', '⁉️', '🔅', '🔆', '〽️', '⚠️', '🚸', '🔱', '⚜️', '🔰', '♻️', '✅', '🈯', '💹', '❇️', '✳️', '❎', '🌐', '💠', 'Ⓜ️', '🌀', '💤', '🏧', '🚾', '♿', '🅿️', '🛗', '🈳', '🈂️', '🛂', '🛃', '🛄', '🛅', '🚰', '🚹', '🚺', '🚻', '🚼', '🚮', '🎦', '📶', '🈁', '🔣', 'ℹ️', '🔤', '🔡', '🔠', '🆖', '🆗', '🆙', '🆒', '🆕', '🆓', '0️⃣', '1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟', '🔢', '#️⃣', '*️⃣', '⏏️', '▶️', '⏸️', '⏯️', '⏹️', '⏺️', '⏭️', '⏮️', '⏩', '⏪', '⏫', '⏬', '◀️', '🔼', '🔽', '➡️', '⬅️', '⬆️', '⬇️', '↗️', '↘️', '↙️', '↖️', '↕️', '↔️', '↪️', '↩️', '⤴️', '⤵️', '🔀', '🔁', '🔂', '🔄', '🔃', '🎵', '🎶', '➕', '➖', '➗', '✖️', '♾️', '💲', '💱', '™️', '©️', '®️', '〰️', '➰', '➿', '🔚', '🔙', '🔛', '🔝', '🔜', '✔️', '☑️', '🔘', '🔴', '🟠', '🟡', '🟢', '🔵', '🟣', '⚫', '⚪', '🟤', '🔺', '🔻', '🔸', '🔹', '🔶', '🔷', '🔳', '🔲', '▪️', '▫️', '◾', '◽', '◼️', '◻️', '🟥', '🟧', '🟨', '🟩', '🟦', '🟪', '⬛', '⬜', '🟫', '🔈', '🔇', '🔉', '🔊', '🔔', '🔕', '📣', '📢', '👁️‍🗨️', '💬', '💭', '🗯️', '♠️', '♣️', '♥️', '♦️', '🃏', '🎴', '🀄', '🕐', '🕑', '🕒', '🕓', '🕔', '🕕', '🕖', '🕗', '🕘', '🕙', '🕚', '🕛', '🕜', '🕝', '🕞', '🕟', '🕠', '🕡', '🕢', '🕣', '🕤', '🕥', '🕦', '🕧']
                }
            ]
        }
    },

    computed: {
        currentCategory() {
            return this.categories.find(c => c.key === this.activeCategory)
        },

        filteredEmojis() {
            if (!this.searchQuery) {
                return this.currentCategory?.emojis || []
            }

            // Поиск по всем категориям
            const allEmojis = this.categories.flatMap(c => c.emojis)
            return allEmojis.filter(emoji => {
                // Простой поиск — эмодзи содержит поисковый запрос
                return emoji.includes(this.searchQuery) ||
                    this.searchQuery.length === 1 && emoji === this.searchQuery
            })
        }
    },

    watch: {
        isOpen(val) {
            if (val) {
                this.$nextTick(() => {
                    this.$refs.searchInput?.focus()
                })
                document.addEventListener('click', this.handleClickOutside)
            } else {
                document.removeEventListener('click', this.handleClickOutside)
            }
        },

        searchQuery() {
            // При поиске сбрасываем активную категорию
            if (this.searchQuery) {
                this.activeCategory = 'search'
            } else {
                this.activeCategory = 'food'
            }
        }
    },

    methods: {
        togglePicker() {
            this.isOpen = !this.isOpen
        },

        selectEmoji(emoji) {
            this.$emit('update:modelValue', emoji)
            this.isOpen = false
            this.searchQuery = ''
        },

        removeEmoji() {
            this.$emit('update:modelValue', '')
        },

        handleClickOutside(e) {
            if (this.$refs.pickerWrapper && !this.$refs.pickerWrapper.contains(e.target)) {
                this.isOpen = false
            }
        }
    },

    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside)
    }
}
</script>

<style scoped>
.emoji-picker-wrapper {
    position: relative;
    display: inline-block;
}

.emoji-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.15s ease;
    font-size: 18px;
    position: relative;
}

.emoji-trigger:hover {
    border-color: #0d6efd;
    color: #0d6efd;
    background: #f8f9ff;
}

.selected-emoji {
    font-size: 20px;
    line-height: 1;
}

.remove-emoji {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #dc3545;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.emoji-trigger:hover .remove-emoji {
    opacity: 1;
}

/* === Picker === */
.emoji-picker {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    width: 360px;
    max-height: 440px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* === Search === */
.picker-search {
    position: relative;
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
}

.picker-search > i {
    position: absolute;
    left: 24px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 12px;
}

.picker-search input {
    width: 100%;
    padding: 8px 12px 8px 32px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
    background: #f8f9fa;
}

.picker-search input:focus {
    background: #fff;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

/* === Tabs === */
.picker-tabs {
    display: flex;
    gap: 2px;
    padding: 8px;
    border-bottom: 1px solid #e9ecef;
    background: #fafbfc;
    overflow-x: auto;
    scrollbar-width: none;
}

.picker-tabs::-webkit-scrollbar {
    display: none;
}

.picker-tab {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 6px 10px;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
    min-width: 54px;
}

.picker-tab:hover {
    background: #e9ecef;
    color: #495057;
}

.picker-tab.active {
    background: #e7f1ff;
    color: #0d6efd;
}

.tab-icon {
    font-size: 18px;
    line-height: 1;
}

.tab-label {
    font-size: 9px;
    font-weight: 500;
    white-space: nowrap;
}

/* === Grid === */
.picker-grid {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(36px, 1fr));
    gap: 4px;
    max-height: 280px;
}

.emoji-item {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 8px;
    background: transparent;
    font-size: 22px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.emoji-item:hover {
    background: #e7f1ff;
    transform: scale(1.2);
}

.picker-empty {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    color: #adb5bd;
    gap: 8px;
}

.picker-empty i {
    font-size: 32px;
    opacity: 0.5;
}

.picker-empty span {
    font-size: 13px;
}

/* === Footer === */
.picker-footer {
    padding: 8px 12px;
    border-top: 1px solid #e9ecef;
    background: #fafbfc;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #6c757d;
}

.picker-footer i {
    color: #0d6efd;
    font-size: 11px;
}

/* === Transitions === */
.picker-enter-active,
.picker-leave-active {
    transition: all 0.2s ease;
}

.picker-enter-from,
.picker-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

/* === Responsive === */
@media (max-width: 576px) {
    .emoji-picker {
        width: calc(100vw - 40px);
        left: -20px;
    }

    .picker-tabs {
        gap: 4px;
    }

    .picker-tab {
        min-width: 48px;
        padding: 6px 8px;
    }

    .tab-label {
        display: none;
    }
}
</style>
